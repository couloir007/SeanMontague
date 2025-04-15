import{p as E}from"./index-C-ZRuDWU.js";import{T as _,t as I}from"./twig-CEFeP58X.js";import{a as M,D as O}from"./twig-D5Zl0i6Y.js";M(_);_.cache(!1);const p=t=>t,P=(t={})=>{const n=I.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/elements/messages/messages.twig",data:[{type:"output",position:{start:0,end:40},stack:[{type:"Twig.expression.type._function",position:{start:0,end:40},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:40}},{type:"Twig.expression.type.string",value:"surface/messages",position:{start:0,end:40}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:40},expression:!1}]}]},{type:"raw",value:`

<div class="messages fade`,position:{start:40,end:67}},{type:"output",position:{start:67,end:98},stack:[{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:67,end:98}},{type:"Twig.expression.type.string",value:" ",position:{start:67,end:98}},{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:67,end:98}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:67,end:98},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:67,end:98},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"output",position:{start:98,end:131},stack:[{type:"Twig.expression.type.variable",value:"type",match:["type"],position:{start:98,end:131}},{type:"Twig.expression.type.string",value:" messages--",position:{start:98,end:131}},{type:"Twig.expression.type.variable",value:"type",match:["type"],position:{start:98,end:131}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:98,end:131},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:98,end:131},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"output_whitespace_both",position:{start:131,end:174},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:131,end:174}},{type:"Twig.expression.type.string",value:" ",position:{start:131,end:174}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:131,end:174}},{type:"Twig.expression.type.key.period",position:{start:131,end:174},key:"class"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:131,end:174},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:131,end:174},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:'" data-drupal-selector="messages" role="contentinfo" aria-label="',position:{start:174,end:239}},{type:"output",position:{start:239,end:252},stack:[{type:"Twig.expression.type.variable",value:"heading",match:["heading"],position:{start:239,end:252}}]},{type:"raw",value:'"',position:{start:252,end:254}},{type:"output_whitespace_both",position:{start:254,end:300},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:254,end:300}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:254,end:300}},{type:"Twig.expression.type.filter",value:"without",match:["|without","without"],position:{start:254,end:300},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:254,end:300}},{type:"Twig.expression.type.variable",value:"class",match:["class"],position:{start:254,end:300}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:254,end:300},expression:!1}]},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:254,end:300},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`>
  <div class="messages__container" data-drupal-selector="messages-container"`,position:{start:300,end:378}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"type",match:["type"]},{type:"Twig.expression.type.string",value:"error"},{type:"Twig.expression.type.operator.binary",value:"==",precidence:9,associativity:"leftToRight",operator:"=="}],position:{start:378,end:402},output:[{type:"raw",value:' role="alert"',position:{start:402,end:415}}]},position:{open:{start:378,end:402},close:{start:415,end:426}}},{type:"raw",value:`>
    `,position:{start:426,end:432}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"heading",match:["heading"]}],position:{start:432,end:448},output:[{type:"raw",value:`      <div class="messages__header">
        <h2 class="visually-hidden">`,position:{start:449,end:522}},{type:"output",position:{start:522,end:535},stack:[{type:"Twig.expression.type.variable",value:"heading",match:["heading"],position:{start:522,end:535}}]},{type:"raw",value:`</h2>
        <span class="fa-solid fa-circle-info"></span>
      </div>
    `,position:{start:535,end:612}}]},position:{open:{start:432,end:448},close:{start:612,end:623}}},{type:"raw",value:`
    <div class="messages__content">
      `,position:{start:624,end:667}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"messages",match:["messages"]},{type:"Twig.expression.type.filter",value:"length",match:["|length","length"]},{type:"Twig.expression.type.number",value:1,match:["1",null]},{type:"Twig.expression.type.operator.binary",value:">",precidence:8,associativity:"leftToRight",operator:">"}],position:{start:667,end:695},output:[{type:"raw",value:`        <ul class="messages__list">
          `,position:{start:696,end:742}},{type:"logic",token:{type:"Twig.logic.type.for",keyVar:null,valueVar:"message",expression:[{type:"Twig.expression.type.variable",value:"messages",match:["messages"]}],position:{start:742,end:771},output:[{type:"raw",value:'            <li class="messages__item">',position:{start:772,end:811}},{type:"output",position:{start:811,end:824},stack:[{type:"Twig.expression.type.variable",value:"message",match:["message"],position:{start:811,end:824}}]},{type:"raw",value:`</li>
          `,position:{start:824,end:840}}]},position:{open:{start:742,end:771},close:{start:840,end:852}}},{type:"raw",value:`        </ul>
      `,position:{start:853,end:873}}]},position:{open:{start:667,end:695},close:{start:873,end:883}}},{type:"logic",token:{type:"Twig.logic.type.else",match:["else"],position:{start:873,end:883},output:[{type:"raw",value:"        ",position:{start:884,end:892}},{type:"output",position:{start:892,end:912},stack:[{type:"Twig.expression.type.variable",value:"messages",match:["messages"],position:{start:892,end:912}},{type:"Twig.expression.type.filter",value:"first",match:["|first","first"],position:{start:892,end:912}}]},{type:"raw",value:`
      `,position:{start:912,end:919}}]},position:{open:{start:873,end:883},close:{start:919,end:930}}},{type:"raw",value:`    </div>
  </div>
</div>
`,position:{start:931,end:931}}],precompiled:!0});n.options.allowInlineIncludes=!0;try{return p(n.render({attributes:new O,...t}))}catch(S){return p("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/elements/messages/messages.twig: "+S.toString())}},o={modifier:"",type:"error",heading:"Message",messages:['This is a message about <em class="placeholder">something</em> that has been updated. <a href="#">This is a link</a>.']},R={title:"Elements/Messages"},e={name:"Messages",render:t=>E(P(t)),args:{...o}},s={...e,name:"Info message",args:{...o,type:"info"}},a={...e,name:"Status message",args:{...o,type:"status"}},r={...e,name:"Warning message",args:{...o,type:"warning"}},i={...e,name:"Error message",args:{...o,type:"error"}};var c,l,y;e.parameters={...e.parameters,docs:{...(c=e.parameters)==null?void 0:c.docs,source:{originalSource:`{
  name: 'Messages',
  render: args => parse(messages(args)),
  args: {
    ...data
  }
}`,...(y=(l=e.parameters)==null?void 0:l.docs)==null?void 0:y.source}}};var d,g,u;s.parameters={...s.parameters,docs:{...(d=s.parameters)==null?void 0:d.docs,source:{originalSource:`{
  ...Success,
  name: 'Info message',
  args: {
    ...data,
    type: 'info'
  }
}`,...(u=(g=s.parameters)==null?void 0:g.docs)==null?void 0:u.source}}};var m,v,w;a.parameters={...a.parameters,docs:{...(m=a.parameters)==null?void 0:m.docs,source:{originalSource:`{
  ...Success,
  name: 'Status message',
  args: {
    ...data,
    type: 'status'
  }
}`,...(w=(v=a.parameters)==null?void 0:v.docs)==null?void 0:w.source}}};var h,T,f;r.parameters={...r.parameters,docs:{...(h=r.parameters)==null?void 0:h.docs,source:{originalSource:`{
  ...Success,
  name: 'Warning message',
  args: {
    ...data,
    type: 'warning'
  }
}`,...(f=(T=r.parameters)==null?void 0:T.docs)==null?void 0:f.source}}};var x,b,k;i.parameters={...i.parameters,docs:{...(x=i.parameters)==null?void 0:x.docs,source:{originalSource:`{
  ...Success,
  name: 'Error message',
  args: {
    ...data,
    type: 'error'
  }
}`,...(k=(b=i.parameters)==null?void 0:b.docs)==null?void 0:k.source}}};const W=["Success","Info","Status","Warning","Error"],A=Object.freeze(Object.defineProperty({__proto__:null,Error:i,Info:s,Status:a,Success:e,Warning:r,__namedExportsOrder:W,default:R},Symbol.toStringTag,{value:"Module"}));export{i as E,s as I,A as M,e as S,r as W,a};
