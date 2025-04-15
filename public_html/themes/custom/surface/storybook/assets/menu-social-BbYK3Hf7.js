import{T as n,t as i}from"./twig-CEFeP58X.js";import{a as o,D as r}from"./twig-D5Zl0i6Y.js";o(n);n.cache(!1);const a=t=>t,c=(t={})=>{const e=i.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/menu-social/menu-social.twig",data:[{type:"raw",value:`<ul class="menu menu--social">
  `,position:{start:0,end:33}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"facebook_url",match:["facebook_url"]}],position:{start:33,end:54},output:[{type:"raw",value:`  <li class="menu__item">
    <a class="menu__link`,position:{start:55,end:105}},{type:"output",position:{start:105,end:118},stack:[{type:"Twig.expression.type.variable",value:"inverse",match:["inverse"],position:{start:105,end:118}}]},{type:"raw",value:'" href="',position:{start:118,end:126}},{type:"output",position:{start:126,end:144},stack:[{type:"Twig.expression.type.variable",value:"facebook_url",match:["facebook_url"],position:{start:126,end:144}}]},{type:"raw",value:`" target="_blank">
      <span class="fa-brands fa-facebook"></span>
      <span class="visually-hidden">Facebook</span>
    </a>
  </li>
  `,position:{start:144,end:284}}]},position:{open:{start:33,end:54},close:{start:284,end:295}}},{type:"raw",value:`
  `,position:{start:296,end:299}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"flickr_url",match:["flickr_url"]}],position:{start:299,end:318},output:[{type:"raw",value:`    <li class="menu__item">
      <a class="menu__link`,position:{start:319,end:373}},{type:"output",position:{start:373,end:386},stack:[{type:"Twig.expression.type.variable",value:"inverse",match:["inverse"],position:{start:373,end:386}}]},{type:"raw",value:' flickr" href="',position:{start:386,end:401}},{type:"output",position:{start:401,end:417},stack:[{type:"Twig.expression.type.variable",value:"flickr_url",match:["flickr_url"],position:{start:401,end:417}}]},{type:"raw",value:`" target="_blank">
`,position:{start:417,end:436}},{type:"raw",value:`

        <span class="visually-hidden">flickr</span>
      </a>
    </li>
  `,position:{start:489,end:566}}]},position:{open:{start:299,end:318},close:{start:566,end:577}}},{type:"raw",value:`</ul>
`,position:{start:578,end:578}}],precompiled:!0});e.options.allowInlineIncludes=!0;try{return a(e.render({attributes:new r,...t}))}catch(s){return a("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/menu-social/menu-social.twig: "+s.toString())}};export{c as s};
