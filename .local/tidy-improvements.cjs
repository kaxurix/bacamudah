const fs=require('node:fs'),path=require('node:path');const root=path.join(__dirname,'..');
let file=path.join(root,'includes/ui.php');let text=fs.readFileSync(file,'utf8');
text=text.replace(/function ilustrasi_buku\(\): string\n\{[\s\S]*?\n\}\n\n(?=function sampul_bacaan)/,'');
text=text.replace(/<span class="sampul-edisi">BACA MUDAH \/ KOLEKSI ' \.[\s\S]*?<\/span>' \./,"' .");
fs.writeFileSync(file,text);
file=path.join(root,'assets/css/style.css');text=fs.readFileSync(file,'utf8').replace(/^\.sampul-edisi \{[^\n]+\n/m,'');fs.writeFileSync(file,text);
file=path.join(root,'login.php');text=fs.readFileSync(file,'utf8').replace(/    \} else \{\n([\s\S]*?)\n    \}\n\}/,(_,body)=>'    } else {\n'+body.split('\n').map(l=>l?'    '+l:l).join('\n')+'\n    }\n}');fs.writeFileSync(file,text);
file=path.join(root,'register.php');text=fs.readFileSync(file,'utf8').replace('Password harus 8–72 byte (maksimal 72 karakter Latin).','Password minimal 8 karakter dan tidak boleh terlalu panjang.');fs.writeFileSync(file,text);
console.log('Ilustrasi pengantar yang tidak terpakai dan nomor dekoratif dibersihkan.');
