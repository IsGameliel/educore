<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer"><title>Opening your school | Educore</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui;background:#f3f5fa;color:#172338}main{padding:32px;max-width:520px;background:white;border-radius:16px;margin:20px}button{padding:12px 20px;border:0;border-radius:8px;background:#174c85;color:white;font:inherit;cursor:pointer}</style></head>
<body><main><h1>Opening {{ $institution->name }}</h1><p>Your school dashboard is ready. You'll arrive there shortly.</p>
<form id="school-portal-entry" method="post" action="{{ $action }}"><input type="hidden" name="token" value="{{ $token }}"><button>Continue to school dashboard</button></form>
<p>If the portal address cannot be reached, confirm your school's domain is configured, then return to your institution account and try again.</p></main>
<script>document.getElementById('school-portal-entry').submit();</script></body></html>
