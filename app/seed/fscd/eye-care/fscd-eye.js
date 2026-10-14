/* ---- block 1 ---- */

(function(){
 var root=document.getElementById('fscd-eye');if(!root)return;
 var progress=document.getElementById('progress');

 if('IntersectionObserver' in window){
  root.classList.add('motion-ready');
  var rio=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('on');rio.unobserve(e.target)}})},{threshold:.08,rootMargin:'0px 0px -25px 0px'});
  root.querySelectorAll('.reveal').forEach(function(el){rio.observe(el)});

  var steps=[].slice.call(root.querySelectorAll('.platform-step')),imgs=[].slice.call(root.querySelectorAll('.platform-img')),pk=document.getElementById('pk'),pt=document.getElementById('pt'),pc=document.getElementById('pc');
  function setStage(i){steps.forEach(function(s,n){s.classList.toggle('active',n===i)});imgs.forEach(function(im,n){im.classList.toggle('active',n===i)});var s=steps[i];if(s){pk.textContent=s.dataset.k;pt.textContent=s.dataset.t;pc.textContent=s.dataset.c}}
  var sio=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting)setStage(parseInt(e.target.dataset.step||'0',10))})},{threshold:.55,rootMargin:'-10% 0px -20% 0px'});
  steps.forEach(function(s){sio.observe(s)});
 }

 function update(){var y=window.scrollY||0,max=Math.max(document.documentElement.scrollHeight-innerHeight,1);if(progress)progress.style.width=Math.min(100,y/max*100)+'%'}
 addEventListener('scroll',update,{passive:true});update();
})();


/* ---- block 2 ---- */

(function(){
  var video=document.getElementById('eyer2-video');
  var button=document.getElementById('eyer-video-control');
  var bar=document.getElementById('eyer-video-progress');
  if(!video)return;

  function setButton(){
    if(!button)return;
    var icon=button.querySelector('i');
    if(icon)icon.className=video.paused?'fa-solid fa-play':'fa-solid fa-pause';
    button.setAttribute('aria-label',video.paused?'Play video':'Pause video');
  }

  if(button){
    button.addEventListener('click',function(){
      if(video.paused){
        var p=video.play();
        if(p&&p.catch)p.catch(function(){});
      }else{
        video.pause();
      }
      setButton();
    });
  }

  video.addEventListener('timeupdate',function(){
    if(!bar||!video.duration)return;
    bar.style.width=Math.min(100,(video.currentTime/video.duration)*100)+'%';
  });

  video.addEventListener('play',setButton);
  video.addEventListener('pause',setButton);

  if('IntersectionObserver' in window){
    var vio=new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting && entry.intersectionRatio>.35){
          var p=video.play();
          if(p&&p.catch)p.catch(function(){});
        }else{
          video.pause();
        }
      });
    },{threshold:[0,.35,.7]});
    vio.observe(video);
  }else{
    var p=video.play();
    if(p&&p.catch)p.catch(function(){});
  }

  setButton();
})();


/* ---- block 3 ---- */

(function(){
  "use strict";
  const topShell=document.getElementById("fscd-master-shell-top");
  const bottomShell=document.getElementById("fscd-master-shell-bottom");
  if(!topShell)return;

  const menuButton=topShell.querySelector("#fscd-menu-toggle");
  const nav=topShell.querySelector("#fscd-nav");
  const megaItems=Array.from(topShell.querySelectorAll(".fscd-has-mega"));
  const backToTop=bottomShell?bottomShell.querySelector("#fscd-back-top"):null;
  const year=bottomShell?bottomShell.querySelector("#fscd-year"):null;
  if(year)year.textContent=new Date().getFullYear();

  function isMobile(){return window.matchMedia("(max-width: 1000px)").matches;}
  function setMega(item,open){
    if(!item)return;
    item.classList.toggle("fscd-mega-open",!!open);
    const trigger=item.querySelector(".fscd-nav-trigger");
    if(trigger)trigger.setAttribute("aria-expanded",open?"true":"false");
  }
  function closeMegas(except){
    megaItems.forEach(function(item){if(item!==except)setMega(item,false);});
  }
  function resetMenuButton(){
    if(!menuButton||!nav)return;
    menuButton.setAttribute("aria-expanded","false");
    const icon=menuButton.querySelector("i");
    if(icon)icon.className="fa-solid fa-bars";
  }
  function closeMobileNav(){
    if(!nav)return;
    nav.classList.remove("fscd-open");
    resetMenuButton();
    closeMegas();
  }

  if(menuButton&&nav){
    menuButton.addEventListener("click",function(e){
      e.preventDefault();
      e.stopPropagation();
      const opening=!nav.classList.contains("fscd-open");
      nav.classList.toggle("fscd-open",opening);
      menuButton.setAttribute("aria-expanded",opening?"true":"false");
      const icon=menuButton.querySelector("i");
      if(icon)icon.className=opening?"fa-solid fa-xmark":"fa-solid fa-bars";
      if(!opening)closeMegas();
    });
  }

  megaItems.forEach(function(item){
    const trigger=item.querySelector(".fscd-nav-trigger");
    if(!trigger)return;

    trigger.addEventListener("click",function(e){
      e.preventDefault();
      e.stopPropagation();
      const opening=!item.classList.contains("fscd-mega-open");
      closeMegas(item);
      setMega(item,opening);
    });

    item.addEventListener("mouseenter",function(){
      if(isMobile())return;
      closeMegas(item);
      setMega(item,true);
    });
    item.addEventListener("mouseleave",function(){
      if(isMobile())return;
      setMega(item,false);
    });
    item.addEventListener("focusin",function(){
      if(isMobile())return;
      closeMegas(item);
      setMega(item,true);
    });
    item.addEventListener("focusout",function(){
      if(isMobile())return;
      window.setTimeout(function(){
        if(!item.contains(document.activeElement))setMega(item,false);
      },0);
    });
  });

  topShell.querySelectorAll(".fscd-nav-link, .fscd-mega a").forEach(function(link){
    link.addEventListener("click",function(){
      closeMegas();
      if(isMobile())closeMobileNav();
    });
  });

  document.addEventListener("click",function(e){
    if(!e.target.closest(".fscd-has-mega"))closeMegas();
    if(isMobile()&&nav&&nav.classList.contains("fscd-open")&&!topShell.contains(e.target))closeMobileNav();
  });

  document.addEventListener("keydown",function(e){
    if(e.key!=="Escape")return;
    const openItem=megaItems.find(function(item){return item.classList.contains("fscd-mega-open");});
    closeMegas();
    if(openItem){
      const trigger=openItem.querySelector(".fscd-nav-trigger");
      if(trigger)trigger.focus();
    }
    if(isMobile())closeMobileNav();
  });

  let lastMobile=isMobile();
  window.addEventListener("resize",function(){
    const nowMobile=isMobile();
    if(nowMobile!==lastMobile){
      closeMegas();
      if(nav)nav.classList.remove("fscd-open");
      resetMenuButton();
      lastMobile=nowMobile;
    }
  },{passive:true});

  window.addEventListener("scroll",function(){
    if(backToTop)backToTop.classList.toggle("fscd-show",window.scrollY>650);
  },{passive:true});

  if(backToTop){
    backToTop.addEventListener("click",function(){
      window.scrollTo({top:0,behavior:"smooth"});
    });
  }
})();
