<?php
require __DIR__.'/../../vendor/autoload.php';$app=require __DIR__.'/../../bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,File,Http};use Illuminate\Support\Str;use App\Services\Tenancy\LegacyDocumentAudit;
if(!$app->environment('local','testing'))throw new RuntimeException('Local recovery only');
$root=storage_path('app/backups/legacy-document-recovery/'.now()->format('Ymd-His').'-'.Str::uuid());File::ensureDirectoryExists($root,0700);
$audit=app(LegacyDocumentAudit::class)->inspect(DB::getDefaultConnection(),storage_path('app'));$items=[];
foreach($audit['missing_references']as$ref){$url=$ref['path'];$image=$ref['table']==='users';if($image)$url='https://academics.mudiameuniversity.edu.ng/storage/'.$url;
if(parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_HOST)!=='academics.mudiameuniversity.edu.ng')continue;$items[$url]=['url'=>$url,'image'=>$image,'references'=>[]];}
$report=[];foreach($items as$i=>$item){$path=$root.'/download-'.count($report).'.partial';$entry=['url'=>$item['url'],'type'=>$item['image']?'photo':'transcript'];
try{$response=Http::timeout(15)->withOptions(['allow_redirects'=>false,'sink'=>$path,'progress'=>function($total,$downloaded){if($downloaded>20*1024*1024)throw new RuntimeException('Recovery size limit');}])->get($item['url']);$entry['status']=$response->status();chmod($path,0600);$valid=$response->successful()&&($item['image']?(bool)@getimagesize($path):str_starts_with(file_get_contents($path,false,null,0,5),'%PDF-'));
if($valid){$to=$root.'/recovered-'.count($report).($item['image']?'.image':'.pdf');rename($path,$to);$entry['recovered_file']=basename($to);$entry['sha256']=hash_file('sha256',$to);}else{File::delete($path);$entry['recovered']=false;}}
catch(Throwable $e){if(is_file($path))File::delete($path);$entry['failure_type']=$e::class;}$report[]=$entry;}
File::put($root.'/recovery.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));chmod($root.'/recovery.json',0600);
echo json_encode(['unique_sources_checked'=>count($report),'recovered'=>count(array_filter($report,fn($r)=>isset($r['recovered_file']))),'http_status_counts'=>array_count_values(array_filter(array_column($report,'status'))),'private_report'=>$root.'/recovery.json']);
