<?php
// Operator rehearsal only: snapshot real school, restore into disposable source,
// import into disposable shared platform, verify, roll back by removing only own QA databases.
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Central\{Institution,Plan,SubscriptionInvoice,SubscriptionPayment};
use App\Models\{User,Faculty,Result,Payment};
use App\Services\Tenancy\{LegacyInstitutionImport,InstitutionProvisioner,InstitutionArchives,InstitutionTasks,ImportedInstitutionActivation,TenantContext,TenantState};
use App\Services\Backups\DatabaseBackups;
use Illuminate\Support\Facades\{Artisan,DB,File,Hash,Http,Storage};
use Illuminate\Support\Str;
function migrationCheck(bool $ok,string $label):void{if(!$ok)throw new RuntimeException('FAILED: '.$label);echo 'PASS: '.$label.PHP_EOL;}
if(!$app->environment('local','testing'))throw new RuntimeException('Rehearse on a local/staging copy environment.');
$originalDefault=DB::getDefaultConnection();$originalCentral=config('database.connections.central');$legacy=config('database.connections.'.$originalDefault);
if(($legacy['driver']??'')!=='mysql')throw new RuntimeException('MySQL rehearsal required.');
$id='educore_migration_'.date('Ymd').'_'.bin2hex(random_bytes(6));$qaRoot=storage_path('framework/testing/'.$id);$reportRoot=storage_path('app/backups/migration-rehearsals/'.$id);$created=[];$exit=0;$summary=['rehearsal_id'=>$id,'status'=>'started','checks'=>[]];
try{
    File::ensureDirectoryExists($qaRoot,0700);File::ensureDirectoryExists($reportRoot,0700);
    config(['database.connections.migration_control'=>[...$legacy,'database'=>null,'url'=>null]]);$control=DB::connection('migration_control');
    foreach(['source','shared']as$suffix){$name=$id.'_'.$suffix;if(!preg_match('/\Aeducore_migration_[0-9]{8}_[a-f0-9]{12}_(source|shared)\z/D',$name)||$control->table('information_schema.SCHEMATA')->where('SCHEMA_NAME',$name)->exists())throw new RuntimeException('Unsafe or existing disposable database.');$control->statement('CREATE DATABASE '.$name.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$created[]=$name;}
    $previousDirectory=config('backups.directory');config(['backups.directory'=>$reportRoot]);
    $backup=app(DatabaseBackups::class)->create();migrationCheck(app(DatabaseBackups::class)->inspect($backup)['complete'],'Legacy SQL snapshot complete');
    $loader=new class extends DatabaseBackups{public function loadCopy(array $backup,string $database):void{$input=$this->restoreInput($backup['path']);try{$this->importInput($input,$database);}finally{fclose($input);}}};
    $loader->loadCopy($backup,$id.'_source');
    config(['backups.directory'=>$previousDirectory,'database.connections.legacy_snapshot'=>[...$legacy,'database'=>$id.'_source','url'=>null],'database.connections.central'=>[...$originalCentral,'database'=>$id.'_shared','url'=>null],
        'tenancy.storage_root'=>$qaRoot.'/tenants','tenancy.lock_directory'=>$qaRoot.'/locks','tenancy.exports.directory'=>$qaRoot.'/exports','tenancy.maintenance_file'=>$qaRoot.'/maintenance.json','mail.default'=>'array','queue.default'=>'database']);
    DB::purge('central');DB::purge('tenant');
    migrationCheck(Artisan::call('migrate',['--database'=>'legacy_snapshot','--force'=>true])===0,'Restored source upgrades independently');
    migrationCheck(Artisan::call('tenancy:install',['--no-interaction'=>true])===0,'Disposable shared schema installed');
    $provisioner=app(InstitutionProvisioner::class);$other=$provisioner->register(['slug'=>'collision-school','name'=>'Collision fixture','domain'=>'collision.educore.test','owner_name'=>'QA Owner','owner_email'=>'collision@example.test','plan_code'=>'platinum','timezone'=>'Africa/Lagos']);
    $provisioner->provision($other,Str::random(40));$context=app(TenantContext::class);
    $context->run($other,function(){Faculty::create(['name'=>'Other school faculty','code'=>'OTH']);Storage::disk('local')->put('unchanged.txt','other institution');});
    $source=DB::connection('legacy_snapshot');$owner=$source->table('users')->where('usertype','admin')->whereNull('deleted_at')->whereNull('merged_into_id')->first();if(!$owner)throw new RuntimeException('No active legacy administrator.');
    if(!DB::connection('central')->table('users')->where('id',$owner->id)->exists())DB::connection('central')->table('users')->insert(['id'=>$owner->id,'tenant_id'=>$other->id,'name'=>'Collision probe','email'=>'probe@example.test','password'=>Hash::make(Str::random(40)),'usertype'=>'student']);
    $otherUsers=DB::connection('central')->table('users')->where('tenant_id',$other->id)->count();
    $otherHash=hash('sha256',json_encode(DB::connection('central')->table('users')->where('tenant_id',$other->id)->get()));
    $school=$provisioner->register(['slug'=>'legacy-copy','name'=>'Existing school migration rehearsal','domain'=>'legacy-copy.educore.test','owner_name'=>$owner->name,'owner_email'=>$owner->email,'plan_code'=>'silver','timezone'=>'Africa/Lagos']);
    $snapshotFiles=$qaRoot.'/source-files';$fileCount=0;
    foreach(['private','public']as$disk){File::ensureDirectoryExists($snapshotFiles.'/'.$disk,0700);$base=realpath(config('filesystems.disks.'.($disk==='private'?'local':'public').'.root'));if(!$base)continue;
        foreach(File::allFiles($base)as$file){$path=$file->getRealPath();if(!$path||$file->isLink()||!str_starts_with($path,$base.DIRECTORY_SEPARATOR))throw new RuntimeException('Unsafe legacy file.');$relative=str_replace('\\','/',substr($path,strlen($base)+1));if($disk==='private'&&(str_starts_with($relative,'tenants/')||str_starts_with($relative,'institution-exports/')))continue;$to=$snapshotFiles.'/'.$disk.'/'.$relative;File::ensureDirectoryExists(dirname($to),0700);copy($path,$to);migrationCheck(hash_file('sha256',$path)===hash_file('sha256',$to),'Source file copy checksum '.(++$fileCount));}
    }
    $documents=app(\App\Services\Tenancy\LegacyDocumentAudit::class)->inspect('legacy_snapshot',$snapshotFiles);File::put($reportRoot.'/document-audit.json',json_encode($documents,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));chmod($reportRoot.'/document-audit.json',0600);$summary['document_audit']=['references'=>$documents['references'],'missing'=>count($documents['missing_references'])];
    $preflight=app(\App\Services\Tenancy\LegacyMigrationPreflight::class)->inspect('legacy_snapshot');File::put($reportRoot.'/preflight.json',json_encode($preflight,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));chmod($reportRoot.'/preflight.json',0600);$summary['preflight']=$preflight;
    if(!$preflight['ready']){
        $summary['quarantine']=app(\App\Services\Tenancy\LegacySnapshotQuarantine::class)->apply('legacy_snapshot',$preflight,$reportRoot.'/quarantined-legacy-rows.json');
        $after=app(\App\Services\Tenancy\LegacyMigrationPreflight::class)->inspect('legacy_snapshot');migrationCheck($after['ready'],'Rehearsal-only archive removes dangling active references');
        File::put($reportRoot.'/preflight-after-rehearsal-archive.json',json_encode($after,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    }
    $importer=app(LegacyInstitutionImport::class);$prepared=$importer->prepare('legacy_snapshot');$result=$importer->import($school,$prepared,$snapshotFiles,true);
    migrationCheck($school->fresh()->status==='imported','Imported school remains blocked for review');
    migrationCheck(SubscriptionInvoice::count()===0&&SubscriptionPayment::count()===0,'Import creates no historical subscription charges');
    $mappedAdmin=$result['maps']['users'][(string)$owner->id];migrationCheck($mappedAdmin!==$owner->id,'Existing global identifier collision is remapped');
    migrationCheck(DB::connection('central')->table('users')->where('id',$mappedAdmin)->value('password')===$owner->password,'Existing administrator password hash preserved');
    migrationCheck($otherHash===hash('sha256',json_encode(DB::connection('central')->table('users')->where('tenant_id',$other->id)->get())),'Other school users unchanged');
    $terms=['plan_code'=>'silver','semester_name'=>'First semester complimentary migration period','semester_start'=>'2026-10-01','semester_end'=>'2027-01-31','student_count'=>max(1,$source->table('users')->where('usertype','student')->count()),'reason'=>'Disposable rehearsal agreement only; does not assign live school terms','agreed_no_charge'=>true];
    app(ImportedInstitutionActivation::class)->activate($school,$terms);
    migrationCheck(SubscriptionInvoice::where('institution_id',$school->id)->value('amount_minor')===0,'Explicit QA migration agreement creates no-charge access');
    $context->run($school->fresh(),function()use($prepared,$result,$mappedAdmin){migrationCheck(User::withoutGlobalScopes()->count()===$prepared['counts']['users'],'All active and archived school users preserved');migrationCheck(Result::withTrashed()->count()===$prepared['counts']['results'],'Academic result records preserved');migrationCheck(Payment::count()===$prepared['counts']['payments'],'Payment records preserved');migrationCheck(User::find($mappedAdmin)!==null,'Mapped users readable through tenant context');});
    $context->run($other,fn()=>migrationCheck(Storage::disk('local')->get('unchanged.txt')==='other institution'&&User::count()===$otherUsers,'Other school rows and files remain isolated'));
    // Archive/restore the imported school and recheck the other institution.
    $archives=app(InstitutionArchives::class);$export=$archives->export($school->fresh(),$mappedAdmin);$archives->restore($school->fresh(),$export,$mappedAdmin);
    migrationCheck($otherHash===hash('sha256',json_encode(DB::connection('central')->table('users')->where('tenant_id',$other->id)->get())),'Restoring migrated school leaves other school unchanged');
    $summary=[...$summary,'status'=>isset($summary['quarantine'])?'passed_with_rehearsal_quarantine':'passed','counts'=>$result['counts'],'files'=>count($result['files']),'source_backup'=>basename($backup['path']),'source_backup_sha256'=>hash_file('sha256',$backup['path']),'no_live_terms_assigned'=>true];
    File::copy($qaRoot.'/tenants/'.$school->id.'/migration-report.json',$reportRoot.'/migration-report.json');
    echo 'Legacy migration and scoped restore rehearsal passed.'.PHP_EOL;
}catch(Throwable $e){$summary['status']='failed';$summary['failure_type']=$e::class;$failure=$e instanceof \Illuminate\Database\QueryException?'Database constraint rejected import (SQLSTATE '.$e->getCode().'); source records require review.':$e->getMessage();$summary['failure']=$failure;echo 'Rehearsal failed: '.$e::class.': '.$failure.PHP_EOL;$exit=1;}
finally{
    DB::purge('tenant');DB::purge('central');DB::purge('legacy_snapshot');config(['database.connections.central'=>$originalCentral]);DB::setDefaultConnection($originalDefault);
    foreach(array_reverse($created)as$name){DB::connection('migration_control')->statement('DROP DATABASE '.$name);echo 'Removed own disposable database: '.$name.PHP_EOL;}
    DB::purge('migration_control');
    if(is_dir($qaRoot)){$resolved=realpath($qaRoot);$base=realpath(storage_path('framework/testing'));if(!$resolved||!$base||!str_starts_with($resolved,$base.DIRECTORY_SEPARATOR)||basename($resolved)!==$id)throw new RuntimeException('Unsafe rehearsal storage cleanup.');File::deleteDirectory($resolved);}
    $summary['cleanup_complete']=true;File::put($reportRoot.'/acceptance.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));chmod($reportRoot.'/acceptance.json',0600);echo 'Private rehearsal report: '.$reportRoot.PHP_EOL;
}
exit($exit);
