import{t as L,T as A}from"./twig-DVM_DCke.js";import{D as E,a as j}from"./twig-CTQ74SmP.js";j(A);A.cache(!1);const u=e=>e,D=(e={})=>{const p=L.twig({id:"/media/sean/0c49c450-91fc-4810-9e56-9c253853713d/Shared/www/SeanMontague/public_html/themes/custom/surface/source/patterns/elements/button/button.twig",data:[{type:"raw",value:`
`,position:{start:150,end:151}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"]},{type:"Twig.expression.type.key.period",key:"url"}],position:{start:151,end:170},output:[{type:"raw",value:'  <a href="',position:{start:171,end:182}},{type:"output",position:{start:182,end:198},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:182,end:198}},{type:"Twig.expression.type.key.period",position:{start:182,end:198},key:"url"}]},{type:"raw",value:'" class="button',position:{start:198,end:213}},{type:"output",position:{start:213,end:263},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:213,end:263}},{type:"Twig.expression.type.key.period",position:{start:213,end:263},key:"modifier"},{type:"Twig.expression.type.string",value:" ",position:{start:213,end:263}},{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:213,end:263}},{type:"Twig.expression.type.key.period",position:{start:213,end:263},key:"modifier"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:213,end:263},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.string",value:"",position:{start:213,end:263}},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:213,end:263},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`">
    `,position:{start:263,end:270}},{type:"output",position:{start:270,end:287},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:270,end:287}},{type:"Twig.expression.type.key.period",position:{start:270,end:287},key:"text"}]},{type:"raw",value:`
    `,position:{start:287,end:292}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"]},{type:"Twig.expression.type.key.period",key:"icon"}],position:{start:292,end:312},output:[{type:"raw",value:'      <span class="',position:{start:313,end:332}},{type:"output",position:{start:332,end:349},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:332,end:349}},{type:"Twig.expression.type.key.period",position:{start:332,end:349},key:"icon"}]},{type:"raw",value:`"></span>
    `,position:{start:349,end:363}}]},position:{open:{start:292,end:312},close:{start:363,end:374}}},{type:"raw",value:`  </a>
`,position:{start:375,end:382}}]},position:{open:{start:151,end:170},close:{start:382,end:392}}},{type:"logic",token:{type:"Twig.logic.type.else",match:["else"],position:{start:382,end:392},output:[{type:"raw",value:'  <button class="button',position:{start:393,end:416}},{type:"output",position:{start:416,end:466},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:416,end:466}},{type:"Twig.expression.type.key.period",position:{start:416,end:466},key:"modifier"},{type:"Twig.expression.type.string",value:" ",position:{start:416,end:466}},{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:416,end:466}},{type:"Twig.expression.type.key.period",position:{start:416,end:466},key:"modifier"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:416,end:466},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.string",value:"",position:{start:416,end:466}},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:416,end:466},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`">
    `,position:{start:466,end:473}},{type:"output",position:{start:473,end:490},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:473,end:490}},{type:"Twig.expression.type.key.period",position:{start:473,end:490},key:"text"}]},{type:"raw",value:`
    `,position:{start:490,end:495}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"]},{type:"Twig.expression.type.key.period",key:"icon"}],position:{start:495,end:515},output:[{type:"raw",value:'      <span class="',position:{start:516,end:535}},{type:"output",position:{start:535,end:552},stack:[{type:"Twig.expression.type.variable",value:"button",match:["button"],position:{start:535,end:552}},{type:"Twig.expression.type.key.period",position:{start:535,end:552},key:"icon"}]},{type:"raw",value:`"></span>
    `,position:{start:552,end:566}}]},position:{open:{start:495,end:515},close:{start:566,end:577}}},{type:"raw",value:`  </button>
`,position:{start:578,end:590}}]},position:{open:{start:382,end:392},close:{start:590,end:601}}}],precompiled:!0});p.options.allowInlineIncludes=!0;try{let o=e.defaultAttributes?e.defaultAttributes:[];return Array.isArray(o)||(o=Object.entries(o)),u(p.render({attributes:new E(o),...e}))}catch(o){return u("An error occurred whilst rendering /media/sean/0c49c450-91fc-4810-9e56-9c253853713d/Shared/www/SeanMontague/public_html/themes/custom/surface/source/patterns/elements/button/button.twig: "+o.toString())}},M={button:{icon:"",modifier:"",url:"https://medschool.ucla.edu",text:"Hi, I am a button"}},W={title:"Elements/Button"},t={render:e=>D(e),args:{...M}},n={...t,name:"Primary button",args:{...t.args,button:{modifier:"button--primary",text:"Primary button"}}},r={...t,name:"Secondary button",args:{...t.args,button:{modifier:"button--secondary",text:"Secondary button",url:"https://medschool.ucla.edu"}}},s={...t,name:"Outlined button",args:{...t.args,button:{modifier:"button--outlined",text:"Outlined button"}}},a={...t,name:"Button with Icon",args:{...t.args,button:{icon:"fa-solid fa-arrow-right fa-sm",modifier:"button--primary",text:"Button with Icon"}}},i={...t,name:"Button as a link",args:{...t.args,button:{icon:"fa-solid fa-arrow-right fa-sm",modifier:"button--link",text:"Button as a link",url:"https://medschool.ucla.edu"}}};var d,c,y;t.parameters={...t.parameters,docs:{...(d=t.parameters)==null?void 0:d.docs,source:{originalSource:`{
  render: args => button(args),
  args: {
    ...data
  }
}`,...(y=(c=t.parameters)==null?void 0:c.docs)==null?void 0:y.source}}};var l,m,g,b,w;n.parameters={...n.parameters,docs:{...(l=n.parameters)==null?void 0:l.docs,source:{originalSource:`{
  ...Button,
  name: 'Primary button',
  args: {
    ...Button.args,
    button: {
      modifier: 'button--primary',
      text: 'Primary button'
    }
  }
}`,...(g=(m=n.parameters)==null?void 0:m.docs)==null?void 0:g.source},description:{story:"Primary button story",...(w=(b=n.parameters)==null?void 0:b.docs)==null?void 0:w.description}}};var f,v,h;r.parameters={...r.parameters,docs:{...(f=r.parameters)==null?void 0:f.docs,source:{originalSource:`{
  ...Button,
  name: 'Secondary button',
  args: {
    ...Button.args,
    button: {
      modifier: 'button--secondary',
      text: 'Secondary button',
      url: 'https://medschool.ucla.edu'
    }
  }
}`,...(h=(v=r.parameters)==null?void 0:v.docs)==null?void 0:h.source}}};var k,x,T;s.parameters={...s.parameters,docs:{...(k=s.parameters)==null?void 0:k.docs,source:{originalSource:`{
  ...Button,
  name: 'Outlined button',
  args: {
    ...Button.args,
    button: {
      modifier: 'button--outlined',
      text: 'Outlined button'
    }
  }
}`,...(T=(x=s.parameters)==null?void 0:x.docs)==null?void 0:T.source}}};var B,S,O;a.parameters={...a.parameters,docs:{...(B=a.parameters)==null?void 0:B.docs,source:{originalSource:`{
  ...Button,
  name: 'Button with Icon',
  args: {
    ...Button.args,
    button: {
      icon: 'fa-solid fa-arrow-right fa-sm',
      modifier: 'button--primary',
      text: 'Button with Icon'
    }
  }
}`,...(O=(S=a.parameters)==null?void 0:S.docs)==null?void 0:O.source}}};var I,P,_;i.parameters={...i.parameters,docs:{...(I=i.parameters)==null?void 0:I.docs,source:{originalSource:`{
  ...Button,
  name: 'Button as a link',
  args: {
    ...Button.args,
    button: {
      icon: 'fa-solid fa-arrow-right fa-sm',
      modifier: 'button--link',
      text: 'Button as a link',
      url: 'https://medschool.ucla.edu'
    }
  }
}`,...(_=(P=i.parameters)==null?void 0:P.docs)==null?void 0:_.source}}};const R=["Button","Primary","Secondary","Outlined","WithIcon","Link"],q=Object.freeze(Object.defineProperty({__proto__:null,Button:t,Link:i,Outlined:s,Primary:n,Secondary:r,WithIcon:a,__namedExportsOrder:R,default:W},Symbol.toStringTag,{value:"Module"}));export{q as B,i as L,s as O,n as P,r as S,a as W,t as a};
