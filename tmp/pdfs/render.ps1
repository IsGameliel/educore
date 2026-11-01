Add-Type -AssemblyName System.Runtime.WindowsRuntime
$asTaskMethod = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1' } | Select-Object -First 1
function Await-Result($operation, $resultType) { $task = $asTaskMethod.MakeGenericMethod($resultType).Invoke($null, @($operation)); $task.Wait(); $task.Result }
[Windows.Storage.StorageFile,Windows.Storage,ContentType=WindowsRuntime] | Out-Null
[Windows.Data.Pdf.PdfDocument,Windows.Data.Pdf,ContentType=WindowsRuntime] | Out-Null
[Windows.Storage.Streams.InMemoryRandomAccessStream,Windows.Storage.Streams,ContentType=WindowsRuntime] | Out-Null
$file = Await-Result ([Windows.Storage.StorageFile]::GetFileFromPathAsync($args[0])) ([Windows.Storage.StorageFile])
$document = Await-Result ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($file)) ([Windows.Data.Pdf.PdfDocument])
Write-Output "Pages: $($document.PageCount)"
for ($i = 0; $i -lt $document.PageCount; $i++) {
 $page = $document.GetPage($i)
 $stream = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
 $action = $page.RenderToStreamAsync($stream)
 $actionMethod = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and -not $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncAction' } | Select-Object -First 1; $task = $actionMethod.Invoke($null, @($action)); $task.Wait()
 $reader = [System.IO.WindowsRuntimeStreamExtensions]::AsStreamForRead($stream)
 $output = [System.IO.File]::Create($args[1] + '-' + ($i+1) + '.png')
 $reader.CopyTo($output); $output.Dispose(); $reader.Dispose(); $page.Dispose()
}

