(function(){
const list=window.FM_PLAYLIST||[]; const player=document.getElementById('bgMusicPlayer'); const now=document.getElementById('musicNowPlaying');
if(!player||!list.length)return; let idx=0;
function load(){player.src=list[idx].url; if(now) now.textContent='Now playing: '+(list[idx].title||('Track '+(idx+1)));}
function next(){idx=(idx+1)%list.length; load(); player.play().catch(()=>{});}
const playBtn=document.getElementById('musicPlayBtn'); const nextBtn=document.getElementById('musicNextBtn');
if(playBtn) playBtn.addEventListener('click',()=>{ if(!player.src) load(); if(player.paused){player.play().catch(()=>{});} else {player.pause();}});
if(nextBtn) nextBtn.addEventListener('click',next); player.addEventListener('ended',next);
})();
(function(){
function addRow(btnId, wrapId, html){ const btn=document.getElementById(btnId), wrap=document.getElementById(wrapId); if(btn&&wrap){btn.addEventListener('click',()=>wrap.insertAdjacentHTML('beforeend',html));}}
addRow('addTimeline','timelineWrap',window.TIMELINE_TEMPLATE||'');
addRow('addGallery','galleryWrap',window.GALLERY_TEMPLATE||'');
addRow('addMusic','musicWrap',window.MUSIC_TEMPLATE||'');
})();
