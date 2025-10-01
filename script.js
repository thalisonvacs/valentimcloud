// script.js - interação do gerenciador
document.addEventListener('DOMContentLoaded', ()=> {
  const navBtns = document.querySelectorAll('.navbtn');
  const views = { files: document.getElementById('view-files'), vault: document.getElementById('view-vault'), settings: document.getElementById('view-settings') };
  const breadcrumbs = document.getElementById('breadcrumbs');
  let currentPath = ''; // relative inside uploads
  let currentView = 'files';

  navBtns.forEach(b=>{
    b.addEventListener('click', ()=> {
      navBtns.forEach(x=>x.classList.remove('active'));
      b.classList.add('active');
      const v = b.dataset.view;
      switchView(v);
    });
  });

  function switchView(v){
    Object.values(views).forEach(el=> el.classList.add('hidden'));
    if(v === 'files') views.files.classList.remove('hidden');
    else if(v === 'vault') views.vault.classList.remove('hidden');
    else views.settings.classList.remove('hidden');
    currentView = v;
    if(v === 'files') loadFiles();
    if(v === 'vault') loadVault();
  }

  // dropzone
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.onchange = ()=> uploadFile(fileInput.files[0]);

  dropzone.addEventListener('click', ()=> fileInput.click());
  dropzone.addEventListener('dragover', e=> { e.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone.addEventListener('dragleave', e=> { dropzone.classList.remove('dragover'); });
  dropzone.addEventListener('drop', e=> {
    e.preventDefault(); dropzone.classList.remove('dragover');
    if(e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
  });

  // load files
  async function loadFiles(){
    breadcrumbs.innerText = '/' + (currentPath || '');
    const res = await fetch(`index.php?action=list&path=${encodeURIComponent(currentPath)}`);
    const json = await res.json();
    const grid = document.getElementById('filesGrid');
    grid.innerHTML = '';
    if(!json.ok) { grid.innerHTML = '<div class="alert error">'+json.error+'</div>'; return; }
    json.items.forEach(it=>{
      const card = document.createElement('div'); card.className='card';
      const top = document.createElement('div'); top.style.display='flex';
      const ic = document.createElement('div'); ic.className='icon-wrap';
      ic.innerHTML = it.is_dir ? '<i class="fa fa-folder"></i>' : '<i class="fa fa-file"></i>';
      const title = document.createElement('div'); title.style.marginLeft='10px';
      title.innerHTML = `<div class="title">${it.name}</div><div class="meta">${it.is_dir ? 'Pasta' : formatBytes(it.size)}</div>`;
      top.appendChild(ic); top.appendChild(title);
      card.appendChild(top);
      const controls = document.createElement('div'); controls.className='controls';
      if(it.is_dir){
        const open = document.createElement('button'); open.innerHTML='<i class="fa fa-folder-open"></i>'; open.title='Abrir';
        open.onclick = ()=> { currentPath = (currentPath? currentPath + '/':'') + it.name; loadFiles(); }
        controls.appendChild(open);
      } else {
        const dl = document.createElement('a'); dl.href = `uploads/${encodeURIComponent(it.name)}`; dl.download = it.name;
        dl.innerHTML = '<button title="Download"><i class="fa fa-download"></i></button>';
        controls.appendChild(dl);
      }
      const renameBtn = document.createElement('button'); renameBtn.innerHTML='<i class="fa fa-pen"></i>'; renameBtn.title='Renomear';
      renameBtn.onclick = ()=> renameItem(it.name);
      const delBtn = document.createElement('button'); delBtn.innerHTML='<i class="fa fa-trash"></i>'; delBtn.title='Excluir';
      delBtn.onclick = ()=> deleteItem(it.name);
      controls.appendChild(renameBtn); controls.appendChild(delBtn);
      card.appendChild(controls);
      grid.appendChild(card);
    });
  }

  function formatBytes(bytes){
    if(bytes === null) return '';
    if(bytes === 0) return '0 B';
    const k = 1024, sizes = ['B','KB','MB','GB','TB'];
    const i = Math.floor(Math.log(bytes)/Math.log(k));
    return parseFloat((bytes/Math.pow(k,i)).toFixed(2)) + ' ' + sizes[i];
  }

  async function uploadFile(file){
    const fd = new FormData();
    fd.append('file', file);
    fd.append('action', 'upload');
    fd.append('path', currentPath);
    const res = await fetch('index.php?action=upload', {method:'POST', body: fd});
    const j = await res.json();
    if(j.ok) { toast('Upload concluído'); loadFiles(); } else toast('Erro: '+j.error, true);
  }

  async function deleteItem(name){
    if(!confirm('Confirma exclusão de "'+name+'"?')) return;
    const fd = new FormData();
    fd.append('action','delete');
    fd.append('path', currentPath);
    fd.append('name', name);
    const res = await fetch('index.php?action=delete',{method:'POST',body:fd});
    const j = await res.json();
    if(j.ok){ toast('Excluído'); loadFiles(); } else toast('Erro: '+j.error,true);
  }

  async function renameItem(oldName){
    const newName = prompt('Novo nome para '+oldName, oldName);
    if(!newName) return;
    const fd = new FormData();
    fd.append('action','rename');
    fd.append('path', currentPath);
    fd.append('old', oldName);
    fd.append('new', newName);
    const res = await fetch('index.php?action=rename',{method:'POST',body:fd});
    const j = await res.json();
    if(j.ok){ toast('Renomeado'); loadFiles(); } else toast('Erro: '+j.error,true);
  }

  // new folder
  document.getElementById('btnNewFolder').addEventListener('click', async ()=>{
    const name = prompt('Nome da nova pasta');
    if(!name) return;
    const fd = new FormData();
    fd.append('action','mkdir'); fd.append('path', currentPath); fd.append('name', name);
    const res = await fetch('index.php?action=mkdir',{method:'POST',body:fd});
    const j = await res.json();
    if(j.ok){ toast('Pasta criada'); loadFiles(); } else toast('Erro: '+j.error,true);
  });

  // vault: load & CRUD
  async function loadVault(){
    const res = await fetch('index.php?action=vault_list');
    const j = await res.json();
    const list = document.getElementById('vaultList');
    list.innerHTML = '';
    if(!j.ok) { list.innerHTML = '<div class="alert error">'+j.error+'</div>'; return; }
    j.items.forEach(it=>{
      const card = document.createElement('div'); card.className='card';
      card.innerHTML = `<div><strong>${it.label || it.id}</strong></div>`;
      const c = document.createElement('div'); c.className='controls';
      const show = document.createElement('button'); show.innerHTML='<i class="fa fa-eye"></i>'; show.onclick = async ()=>{
        const r = await fetch('index.php?action=vault_get&label='+encodeURIComponent(it.id));
        const jr = await r.json();
        if(jr.ok) alert('Senha: '+jr.password); else alert('Erro: '+jr.error);
      };
      const del = document.createElement('button'); del.innerHTML='<i class="fa fa-trash"></i>'; del.onclick = async ()=>{
        if(!confirm('Remover '+it.id+'?')) return;
        const fd = new FormData(); fd.append('action','vault_delete'); fd.append('label', it.id);
        const r = await fetch('index.php?action=vault_delete',{method:'POST',body:fd}); const jr = await r.json();
        if(jr.ok) { toast('Removido'); loadVault(); } else toast('Erro: '+jr.error,true);
      };
      c.appendChild(show); c.appendChild(del);
      card.appendChild(c);
      list.appendChild(card);
    });
  }

  document.getElementById('vaultForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = e.target;
    const label = form.label.value.trim(); const senha = form.senha.value;
    if(!label||!senha) return toast('Preencha', true);
    const fd = new FormData(); fd.append('action','vault_add'); fd.append('label', label); fd.append('senha', senha);
    const res = await fetch('index.php?action=vault_add',{method:'POST',body:fd}); const j = await res.json();
    if(j.ok) { toast('Senha adicionada'); form.reset(); loadVault(); } else toast('Erro: '+j.error,true);
  });

  // toast
  function toast(msg, isErr=false){ const el = document.createElement('div'); el.className='alert '+(isErr?'error':'success'); el.innerText = msg; document.querySelector('.panel').prepend(el); setTimeout(()=>el.remove(),3000); }

  // init
  loadFiles();
});
