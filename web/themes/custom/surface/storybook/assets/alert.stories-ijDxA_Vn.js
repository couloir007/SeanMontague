import{p as B}from"./index-C-ZRuDWU.js";import{S as E,V as W}from"./decorators-7nrNV5GJ.js";import{T as _,t as V}from"./twig-CEFeP58X.js";import{a as A,D as C}from"./twig-D5Zl0i6Y.js";A(_);_.cache(!1);const p=t=>t,q=(t={})=>{const o=V.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/elements/alert/alert.twig",data:[{type:"output",position:{start:0,end:37},stack:[{type:"Twig.expression.type._function",position:{start:0,end:37},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:37}},{type:"Twig.expression.type.string",value:"surface/alert",position:{start:0,end:37}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:37},expression:!1}]}]},{type:"raw",value:`

<div class="alert container`,position:{start:37,end:66}},{type:"output",position:{start:66,end:97},stack:[{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:66,end:97}},{type:"Twig.expression.type.string",value:" ",position:{start:66,end:97}},{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:66,end:97}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:66,end:97},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:66,end:97},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"output_whitespace_both",position:{start:97,end:140},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:97,end:140}},{type:"Twig.expression.type.string",value:" ",position:{start:97,end:140}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:97,end:140}},{type:"Twig.expression.type.key.period",position:{start:97,end:140},key:"class"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:97,end:140},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:97,end:140},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:'"',position:{start:140,end:142}},{type:"output_whitespace_both",position:{start:142,end:188},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:142,end:188}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:142,end:188}},{type:"Twig.expression.type.filter",value:"without",match:["|without","without"],position:{start:142,end:188},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:142,end:188}},{type:"Twig.expression.type.variable",value:"class",match:["class"],position:{start:142,end:188}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:142,end:188},expression:!1}]},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:142,end:188},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`>
  <div class="alert__container">
    <div class="alert__header">
      <h2 class="visually-hidden">Alert</h2>
      <span class="fa-solid fa-circle-info"></span>
    </div>
    <div class="prose">
      `,position:{start:188,end:393}},{type:"output",position:{start:393,end:403},stack:[{type:"Twig.expression.type.variable",value:"text",match:["text"],position:{start:393,end:403}}]},{type:"raw",value:`
    </div>
  </div>
</div>
`,position:{start:403,end:403}}],precompiled:!0});o.options.allowInlineIncludes=!0;try{return p(o.render({attributes:new C,...t}))}catch(k){return p("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/elements/alert/alert.twig: "+k.toString())}},n={modifier:"alert--success",text:"Lorem ipsum dolor sit amet consectetur adipisicing elit. Nostrum a ab quae quas optio ut officia quia? Modi at impedit dolorem est voluptatem facilis, beatae atque tenetur, soluta dolorum inventore sapiente laborum. Alias esse soluta porro distinctio aperiam, qui suscipit."},O={title:"Elements/Alert",parameters:{controls:{include:["text"]}}},e={name:"Success alert",render:t=>B(q(t)),args:{...n}},a={...e,name:"Warning alert",args:{...n,modifier:"alert--warning"}},r={...e,name:"Error alert",args:{...n,modifier:"alert--error"}},s={...e,name:"With Santa Barbara Sand background",decorators:[E]},i={...e,name:"With Venice Canal background",decorators:[W]};var c,l,d;e.parameters={...e.parameters,docs:{...(c=e.parameters)==null?void 0:c.docs,source:{originalSource:`{
  name: 'Success alert',
  render: args => parse(alert(args)),
  args: {
    ...data
  }
}`,...(d=(l=e.parameters)==null?void 0:l.docs)==null?void 0:d.source}}};var u,m,y;a.parameters={...a.parameters,docs:{...(u=a.parameters)==null?void 0:u.docs,source:{originalSource:`{
  ...Success,
  name: 'Warning alert',
  args: {
    ...data,
    modifier: 'alert--warning'
  }
}`,...(y=(m=a.parameters)==null?void 0:m.docs)==null?void 0:y.source}}};var g,w,v;r.parameters={...r.parameters,docs:{...(g=r.parameters)==null?void 0:g.docs,source:{originalSource:`{
  ...Success,
  name: 'Error alert',
  args: {
    ...data,
    modifier: 'alert--error'
  }
}`,...(v=(w=r.parameters)==null?void 0:w.docs)==null?void 0:v.source}}};var b,h,f;s.parameters={...s.parameters,docs:{...(b=s.parameters)==null?void 0:b.docs,source:{originalSource:`{
  ...Success,
  name: 'With Santa Barbara Sand background',
  decorators: [SantaBarbaraSandBg]
}`,...(f=(h=s.parameters)==null?void 0:h.docs)==null?void 0:f.source}}};var x,T,S;i.parameters={...i.parameters,docs:{...(x=i.parameters)==null?void 0:x.docs,source:{originalSource:`{
  ...Success,
  name: 'With Venice Canal background',
  decorators: [VeniceCanalBg]
}`,...(S=(T=i.parameters)==null?void 0:T.docs)==null?void 0:S.source}}};const P=["Success","Warning","Error","SantaBarbaraSand","VeniceCanal"],M=Object.freeze(Object.defineProperty({__proto__:null,Error:r,SantaBarbaraSand:s,Success:e,VeniceCanal:i,Warning:a,__namedExportsOrder:P,default:O},Symbol.toStringTag,{value:"Module"}));export{M as A,r as E,e as S,i as V,a as W,s as a};
