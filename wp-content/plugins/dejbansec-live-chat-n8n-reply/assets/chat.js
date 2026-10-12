(()=>{
'use strict';
const init=()=>{
 const root=document.getElementById('dslc-widget');
 if(!root)return;
 const launch=root.querySelector('.dslc-launch');
 const panel=root.querySelector('.dslc-panel');
 const close=root.querySelector('.dslc-close');
 const form=root.querySelector('.dslc-form');
 const box=root.querySelector('.dslc-messages');
 if(!launch||!panel)return;
 launch.addEventListener('click',(e)=>{e.preventDefault();e.stopPropagation();panel.classList.add('open');});
 if(close) close.addEventListener('click',(e)=>{e.preventDefault();panel.classList.remove('open');});
 const key='dslc_session';
 let sid=localStorage.getItem(key);
 if(!sid){sid='dslc_'+Date.now()+'_'+Math.random().toString(36).slice(2);try{localStorage.setItem(key,sid)}catch(e){}}
 const add=(role,msg)=>{if(!box)return;const d=document.createElement('div');d.className='dslc-msg '+role;d.textContent=msg;box.appendChild(d);box.scrollTop=box.scrollHeight;};
 let lastId=0;
 const loadHistory=async(initial=false)=>{
  try{
   if(!window.DSLC||!DSLC.rest)return;
   const url=DSLC.rest+'?session_id='+encodeURIComponent(sid)+(lastId?'&since_id='+lastId:'');
   const r=await fetch(url,{credentials:'same-origin'});
   const j=await r.json();
   (j.messages||[]).forEach(x=>{
     if(Number(x.id)<=lastId)return;
     if(x.role==='customer')add('user',x.message);
     else if(x.role==='agent'||x.role==='bot')add('bot',x.message);
     lastId=Math.max(lastId,Number(x.id)||0);
   });
  }catch(e){if(initial)console.warn('DejbanSec Live Chat history:',e)}
 };
 if(form) form.addEventListener('submit',async(e)=>{
  e.preventDefault();
  const fd=new FormData(form), msg=String(fd.get('message')||'').trim();
  if(!msg)return;
  add('user',msg);
  const btn=form.querySelector('button'); if(btn)btn.disabled=true;
  try{
   if(!window.DSLC||!DSLC.rest)throw new Error('REST config missing');
   const r=await fetch(DSLC.rest,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':DSLC.nonce||''},body:JSON.stringify({session_id:sid,name:String(fd.get('name')||''),contact:String(fd.get('contact')||''),message:msg})});
   const j=await r.json();
   if(!r.ok||!j.ok)throw new Error(j.message||'send failed');
   const field=form.querySelector('[name="message"]');if(field)field.value='';
   add('bot','پیام شما دریافت شد. به‌زودی پاسخ می‌دهیم.');
  }catch(e){console.error('DejbanSec Live Chat send:',e);add('bot','ارسال پیام انجام نشد. لطفاً دوباره تلاش کنید.');}
  finally{if(btn)btn.disabled=false;}
 });
 loadHistory(true); setInterval(()=>loadHistory(false),3000);
};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
