import{p as u}from"./index-C-ZRuDWU.js";import{w as c}from"./decorators-7nrNV5GJ.js";import{T as o,t as p}from"./twig-CEFeP58X.js";import{a as m,D as d}from"./twig-D5Zl0i6Y.js";m(o);o.cache(!1);const s=e=>e,w=(e={})=>{const n=p.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/menu-utility/menu-utility.twig",data:[{type:"output",position:{start:0,end:44},stack:[{type:"Twig.expression.type._function",position:{start:0,end:44},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:44}},{type:"Twig.expression.type.string",value:"surface/menu-utility",position:{start:0,end:44}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:44},expression:!1}]}]},{type:"raw",value:`

<ul class="menu menu--utility">
  <li class="menu__item">
    <a class="menu__link" href="https://www.bso.ucla.edu/">Emergency</a>
  </li>
  <li class="menu__item">
    <a class="menu__link" href="http://www.ucla.edu/accessibility">Accessibility</a>
  </li>
  <li class="menu__item">
    <a class="menu__link" href="https://www.ucla.edu/terms-of-use">UCLA Privacy Policy</a>
  </li>
  <li class="menu__item">
    <a class="menu__link" href="https://www.uclahealth.org/privacy-notice">UCLA Health Privacy Notice</a>
  </li>
  <li class="menu__item">
    `,position:{start:44,end:599}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"logged_in",match:["logged_in"]}],position:{start:599,end:617},output:[{type:"raw",value:`      <a class="menu__link" rel="nofolow" href="/user/logout">Logout</a>
    `,position:{start:618,end:695}}]},position:{open:{start:599,end:617},close:{start:695,end:705}}},{type:"logic",token:{type:"Twig.logic.type.else",match:["else"],position:{start:695,end:705},output:[{type:"raw",value:`      <a class="menu__link" rel="nofolow" href="/user/login">Login</a>
    `,position:{start:706,end:781}}]},position:{open:{start:695,end:705},close:{start:781,end:792}}},{type:"raw",value:`  </li>
</ul>
`,position:{start:793,end:793}}],precompiled:!0});n.options.allowInlineIncludes=!0;try{return s(n.render({attributes:new d,...e}))}catch(l){return s("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/menu-utility/menu-utility.twig: "+l.toString())}},y={title:"Components/Menu utility"},t={name:"Menu utility",render:e=>u(w(e)),decorators:[c]};var a,i,r;t.parameters={...t.parameters,docs:{...(a=t.parameters)==null?void 0:a.docs,source:{originalSource:`{
  name: 'Menu utility',
  render: args => parse(menu(args)),
  decorators: [withBackground]
}`,...(r=(i=t.parameters)==null?void 0:i.docs)==null?void 0:r.source}}};const g=["menuUtility"],v=Object.freeze(Object.defineProperty({__proto__:null,__namedExportsOrder:g,default:y,menuUtility:t},Symbol.toStringTag,{value:"Module"}));export{v as M,t as m};
