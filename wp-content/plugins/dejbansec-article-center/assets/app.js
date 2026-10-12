
document.addEventListener('DOMContentLoaded',function(){
  const grid=document.getElementById('djsGrid'), search=document.getElementById('djsSearch'), empty=document.getElementById('djsEmpty');
  if(!grid) return;
  const cards=[...grid.querySelectorAll('.djs-card')];
  let cat='all';
  function filter(){
    const q=(search?.value||'').trim().toLowerCase(); let shown=0;
    cards.forEach(c=>{
      const okCat=cat==='all'||c.dataset.category===cat;
      const okText=!q||c.dataset.title.includes(q)||c.textContent.toLowerCase().includes(q);
      c.style.display=okCat&&okText?'':'none'; if(okCat&&okText) shown++;
    });
    if(empty) empty.style.display=shown?'none':'block';
  }
  document.querySelectorAll('.djs-filters button').forEach(b=>b.addEventListener('click',()=>{
    document.querySelectorAll('.djs-filters button').forEach(x=>x.classList.remove('active'));
    b.classList.add('active'); cat=b.dataset.cat; filter();
  }));
  search?.addEventListener('input',filter);
});
