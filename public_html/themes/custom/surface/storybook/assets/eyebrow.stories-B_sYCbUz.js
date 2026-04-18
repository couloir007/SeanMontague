import{t as b,T as c}from"./twig-DVM_DCke.js";import{D as m,a as g}from"./twig-CTQ74SmP.js";g(c);c.cache(!1);const o=e=>e,u=(e={})=>{const a=b.twig({id:"/media/sean/0c49c450-91fc-4810-9e56-9c253853713d/Shared/www/SeanMontague/public_html/themes/custom/surface/source/patterns/elements/eyebrow/eyebrow.twig",data:[{type:"output",position:{start:0,end:39},stack:[{type:"Twig.expression.type._function",position:{start:0,end:39},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:39}},{type:"Twig.expression.type.string",value:"surface/eyebrow",position:{start:0,end:39}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:39},expression:!1}]}]},{type:"raw",value:`

<div class="eyebrow`,position:{start:39,end:60}},{type:"output",position:{start:60,end:88},stack:[{type:"Twig.expression.type.string",value:" ",position:{start:60,end:88}},{type:"Twig.expression.type.variable",value:"eyebrow",match:["eyebrow"],position:{start:60,end:88}},{type:"Twig.expression.type.key.period",position:{start:60,end:88},key:"modifier"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:60,end:88},precidence:6,associativity:"leftToRight",operator:"~"}]},{type:"raw",value:`">
  `,position:{start:88,end:93}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"eyebrow",match:["eyebrow"]},{type:"Twig.expression.type.key.period",key:"url"}],position:{start:93,end:113},output:[{type:"raw",value:'    <a href="',position:{start:114,end:127}},{type:"output",position:{start:127,end:144},stack:[{type:"Twig.expression.type.variable",value:"eyebrow",match:["eyebrow"],position:{start:127,end:144}},{type:"Twig.expression.type.key.period",position:{start:127,end:144},key:"url"}]},{type:"raw",value:'" class="eyebrow__link">',position:{start:144,end:168}},{type:"output",position:{start:168,end:186},stack:[{type:"Twig.expression.type.variable",value:"eyebrow",match:["eyebrow"],position:{start:168,end:186}},{type:"Twig.expression.type.key.period",position:{start:168,end:186},key:"text"}]},{type:"raw",value:`</a>
  `,position:{start:186,end:193}}]},position:{open:{start:93,end:113},close:{start:193,end:203}}},{type:"logic",token:{type:"Twig.logic.type.else",match:["else"],position:{start:193,end:203},output:[{type:"raw",value:'    <span class="eyebrow__text">',position:{start:204,end:236}},{type:"output",position:{start:236,end:254},stack:[{type:"Twig.expression.type.variable",value:"eyebrow",match:["eyebrow"],position:{start:236,end:254}},{type:"Twig.expression.type.key.period",position:{start:236,end:254},key:"text"}]},{type:"raw",value:`</span>
  `,position:{start:254,end:264}}]},position:{open:{start:193,end:203},close:{start:264,end:275}}},{type:"raw",value:`</div>
`,position:{start:276,end:276}}],precompiled:!0});a.options.allowInlineIncludes=!0;try{let t=e.defaultAttributes?e.defaultAttributes:[];return Array.isArray(t)||(t=Object.entries(t)),o(a.render({attributes:new m(t),...e}))}catch(t){return o("An error occurred whilst rendering /media/sean/0c49c450-91fc-4810-9e56-9c253853713d/Shared/www/SeanMontague/public_html/themes/custom/surface/source/patterns/elements/eyebrow/eyebrow.twig: "+t.toString())}},w={eyebrow:{modifier:"",text:"This is a label",url:"#"}},x={title:"Elements/Eyebrow",parameters:{controls:{disable:!0}}},r={name:"Plain text label",render:e=>u(e),args:{...w,eyebrow:{modifier:"",text:"This is a plain text label",url:""}}},s={name:"Label as a link",render:e=>u(e),args:{...w,eyebrow:{modifier:"some-class",text:"This is a label as a link",url:"https://medschool.ucla.edu"}}};var i,n,p;r.parameters={...r.parameters,docs:{...(i=r.parameters)==null?void 0:i.docs,source:{originalSource:`{
  name: 'Plain text label',
  render: args => eyebrow(args),
  args: {
    ...data,
    eyebrow: {
      modifier: '',
      text: 'This is a plain text label',
      url: ''
    }
  }
}`,...(p=(n=r.parameters)==null?void 0:n.docs)==null?void 0:p.source}}};var l,y,d;s.parameters={...s.parameters,docs:{...(l=s.parameters)==null?void 0:l.docs,source:{originalSource:`{
  name: 'Label as a link',
  render: args => eyebrow(args),
  args: {
    ...data,
    eyebrow: {
      modifier: 'some-class',
      text: 'This is a label as a link',
      url: 'https://medschool.ucla.edu'
    }
  }
}`,...(d=(y=s.parameters)==null?void 0:y.docs)==null?void 0:d.source}}};const f=["Eyebrow","Linked"],v=Object.freeze(Object.defineProperty({__proto__:null,Eyebrow:r,Linked:s,__namedExportsOrder:f,default:x},Symbol.toStringTag,{value:"Module"}));export{v as E,s as L,r as a};
