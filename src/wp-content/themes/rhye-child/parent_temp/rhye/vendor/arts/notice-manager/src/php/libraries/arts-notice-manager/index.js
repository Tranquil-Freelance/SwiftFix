(function(global,factory){typeof exports==='object'&&typeof module!=='undefined'?module.exports=factory():typeof define==='function'&&define.amd?define(factory):(global=typeof globalThis!=='undefined'?globalThis:global||self,global.NoticeManager=factory());})(this,function(){
var NoticeManager=(()=>{document.addEventListener("click",t=>{if(!("target"in t)||!(t.target instanceof HTMLElement)||!t.target.classList.contains("notice-dismiss"))return;let i=t.target.closest(".notice.is-dismissible");if(!i)return;let s=i.getAttribute("data-dismiss-url");s&&fetch(s)});})();
});
//# sourceMappingURL=index.umd.js.map
