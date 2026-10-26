import fs from 'node:fs/promises';
import {createHash} from 'node:crypto';
const directory = new URL('../../../node_modules/.cache/editorial-blog-read-lab/', import.meta.url);
const assets = [
  {file:'polylang.3.9.1.zip',sha256:'b09aa8b3d92a882b1d5e24ca2c729981e8ac07d4403f78d4a6853899b20f9266'},
  {file:'mcp-adapter.0.7.1.zip',sha256:'719d81aa1250615acfd8256f0acc8c13f1712f1ae398c3d5883c8b166024fe9b'},
];
await fs.mkdir(directory,{recursive:true});
const manifest=[];
for(const asset of assets){
  const path=new URL(asset.file,directory);
  let bytes;
  try {bytes=await fs.readFile(path);} catch(error){if(error.code!=='ENOENT')throw error;}
  if(!bytes){
    const response=await fetch(`https://downloads.wordpress.org/plugin/${asset.file}`,{redirect:'error',signal:AbortSignal.timeout(30000)});
    if(!response.ok)throw Error('PLUGIN_DOWNLOAD_FAILED');
    bytes=Buffer.from(await response.arrayBuffer());
  }
  if(bytes.length>10*1024*1024||bytes[0]!==80||bytes[1]!==75
    ||createHash('sha256').update(bytes).digest('hex')!==asset.sha256)throw Error('PLUGIN_CHECKSUM_MISMATCH');
  await fs.writeFile(path,bytes);
  manifest.push({...asset,bytes:bytes.length});
}
await fs.writeFile(new URL('manifest.json',directory),JSON.stringify(manifest,null,2)+'\n');
console.log(JSON.stringify(manifest));
