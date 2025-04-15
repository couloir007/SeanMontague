import{p as i}from"./index-C-ZRuDWU.js";import{S as M,V}from"./decorators-7nrNV5GJ.js";import{T as j,t as P}from"./twig-CEFeP58X.js";import{a as W,D}from"./twig-D5Zl0i6Y.js";W(j);j.cache(!1);const d=e=>e,p=(e={})=>{const u=P.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/quote/quote.twig",data:[{type:"output",position:{start:0,end:37},stack:[{type:"Twig.expression.type._function",position:{start:0,end:37},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:37}},{type:"Twig.expression.type.string",value:"surface/quote",position:{start:0,end:37}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:37},expression:!1}]}]},{type:"raw",value:`

<div class="quote`,position:{start:37,end:56}},{type:"output",position:{start:56,end:92},stack:[{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:56,end:92}},{type:"Twig.expression.type.string",value:" ",position:{start:56,end:92}},{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:56,end:92}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:56,end:92},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.string",value:"",position:{start:56,end:92}},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:56,end:92},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"output_whitespace_both",position:{start:92,end:140},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:92,end:140}},{type:"Twig.expression.type.string",value:" ",position:{start:92,end:140}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:92,end:140}},{type:"Twig.expression.type.key.period",position:{start:92,end:140},key:"class"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:92,end:140},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.string",value:"",position:{start:92,end:140}},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:92,end:140},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:'"',position:{start:140,end:142}},{type:"output_whitespace_both",position:{start:142,end:188},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:142,end:188}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:142,end:188}},{type:"Twig.expression.type.filter",value:"without",match:["|without","without"],position:{start:142,end:188},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:142,end:188}},{type:"Twig.expression.type.variable",value:"class",match:["class"],position:{start:142,end:188}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:142,end:188},expression:!1}]},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:142,end:188},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`>
  <div class="quote__content container">
    <div class="quote__text">
      <blockquote class="quote__wrapper">
        `,position:{start:188,end:311}},{type:"output",position:{start:311,end:322},stack:[{type:"Twig.expression.type.variable",value:"quote",match:["quote"],position:{start:311,end:322}}]},{type:"raw",value:`
        <span class="quote__author">`,position:{start:322,end:359}},{type:"output",position:{start:359,end:371},stack:[{type:"Twig.expression.type.variable",value:"author",match:["author"],position:{start:359,end:371}}]},{type:"raw",value:`</span>
      </blockquote>
    </div>
    `,position:{start:371,end:414}},{type:"logic",token:{type:"Twig.logic.type.if",stack:[{type:"Twig.expression.type.variable",value:"image",match:["image"]}],position:{start:414,end:428},output:[{type:"raw",value:`      <div class="quote__image">
        `,position:{start:429,end:470}},{type:"output",position:{start:470,end:481},stack:[{type:"Twig.expression.type.variable",value:"image",match:["image"],position:{start:470,end:481}}]},{type:"raw",value:`
      </div>
    `,position:{start:481,end:499}}]},position:{open:{start:414,end:428},close:{start:499,end:510}}},{type:"raw",value:`  </div>
</div>
`,position:{start:511,end:511}}],precompiled:!0});u.options.allowInlineIncludes=!0;try{return d(u.render({attributes:new D,...e}))}catch(O){return d("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/quote/quote.twig: "+O.toString())}},c={modifier:"",quote:"<p>Donut wafer jelly danish jelly. Cake donut bonbon marshmallow shortbread jelly-o cotton candy lollipop. Wafer jelly-o liquorice carrot cake apple pie fruitcake shortbread gummies.</p>",author:"John Sullivan",image:'<img src="images/3-2.svg" alt="placeholder text" />'},E={title:"Components/Quote"},t={name:"Media Quote",render:e=>i(p(e)),args:{...c}},a={...t,name:"Quote",render:e=>i(p(e)),args:{...c,image:""}},r={...t,name:"Quote with Santa Barbara Sand background",render:e=>i(p(e)),args:{...c,image:""},decorators:[M]},o={...t,name:"Quote with Santa Barbara Sand background",render:e=>i(p(e)),args:{...c,image:""},decorators:[V]},s={...t,name:"With Santa Barbara Sand background",decorators:[M]},n={...t,name:"With Venice Canal background",decorators:[V]};var l,m,g;t.parameters={...t.parameters,docs:{...(l=t.parameters)==null?void 0:l.docs,source:{originalSource:`{
  name: 'Media Quote',
  render: args => parse(quote(args)),
  args: {
    ...data
  }
}`,...(g=(m=t.parameters)==null?void 0:m.docs)==null?void 0:g.source}}};var y,w,b;a.parameters={...a.parameters,docs:{...(y=a.parameters)==null?void 0:y.docs,source:{originalSource:`{
  ...MediaQuote,
  name: 'Quote',
  render: args => parse(quote(args)),
  args: {
    ...data,
    image: ''
  }
}`,...(b=(w=a.parameters)==null?void 0:w.docs)==null?void 0:b.source}}};var v,h,S;r.parameters={...r.parameters,docs:{...(v=r.parameters)==null?void 0:v.docs,source:{originalSource:`{
  ...MediaQuote,
  name: 'Quote with Santa Barbara Sand background',
  render: args => parse(quote(args)),
  args: {
    ...data,
    image: ''
  },
  decorators: [SantaBarbaraSandBg]
}`,...(S=(h=r.parameters)==null?void 0:h.docs)==null?void 0:S.source}}};var T,x,f;o.parameters={...o.parameters,docs:{...(T=o.parameters)==null?void 0:T.docs,source:{originalSource:`{
  ...MediaQuote,
  name: 'Quote with Santa Barbara Sand background',
  render: args => parse(quote(args)),
  args: {
    ...data,
    image: ''
  },
  decorators: [VeniceCanalBg]
}`,...(f=(x=o.parameters)==null?void 0:x.docs)==null?void 0:f.source}}};var k,Q,q;s.parameters={...s.parameters,docs:{...(k=s.parameters)==null?void 0:k.docs,source:{originalSource:`{
  ...MediaQuote,
  name: 'With Santa Barbara Sand background',
  decorators: [SantaBarbaraSandBg]
}`,...(q=(Q=s.parameters)==null?void 0:Q.docs)==null?void 0:q.source}}};var _,B,C;n.parameters={...n.parameters,docs:{...(_=n.parameters)==null?void 0:_.docs,source:{originalSource:`{
  ...MediaQuote,
  name: 'With Venice Canal background',
  decorators: [VeniceCanalBg]
}`,...(C=(B=n.parameters)==null?void 0:B.docs)==null?void 0:C.source}}};const L=["MediaQuote","Quote","QuoteSantaBarbaraSand","QuoteVeniceCanal","SantaBarbaraSand","VeniceCanal"],J=Object.freeze(Object.defineProperty({__proto__:null,MediaQuote:t,Quote:a,QuoteSantaBarbaraSand:r,QuoteVeniceCanal:o,SantaBarbaraSand:s,VeniceCanal:n,__namedExportsOrder:L,default:E},Symbol.toStringTag,{value:"Module"}));export{t as M,J as Q,s as S,n as V,a,r as b,o as c};
