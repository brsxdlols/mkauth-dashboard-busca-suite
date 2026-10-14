const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
const calls=[],context={URL,location:{origin:'https://example.test'},document:{baseURI:'https://example.test/admin/addons/busca_inteligente/index.php'},window:{fetch:(...args)=>{calls.push(args);return Promise.resolve();}}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../addons/shared/admin_asset_paths.js'),'utf8'),context);
const options={method:'POST',credentials:'same-origin',body:'action=batch_jobs'};
context.window.fetch('addons/geocodificacao/geocode.php?action=batch_jobs',options);assert.equal(calls[0][0],'https://example.test/admin/addons/geocodificacao/geocode.php?action=batch_jobs');assert.equal(calls[0][1],options);
context.window.fetch('https://other.test/addons/geocodificacao/geocode.php',options);assert.equal(calls[1][0],'https://other.test/addons/geocodificacao/geocode.php');
context.window.fetch('performance_api.php',options);assert.equal(calls[2][0],'performance_api.php');
console.log('PASS legacy geocoding URL routing preserves POST, options, other origins and unrelated requests.');
