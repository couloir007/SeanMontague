import{p as w}from"./index-C-ZRuDWU.js";import{S as b,V as h}from"./decorators-7nrNV5GJ.js";import{T as g,t as x}from"./twig-CEFeP58X.js";import{a as T,D as f}from"./twig-D5Zl0i6Y.js";T(g);g.cache(!1);const s=e=>e,S=(e={})=>{const i=x.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/metric/metric.twig",data:[{type:"output",position:{start:0,end:38},stack:[{type:"Twig.expression.type._function",position:{start:0,end:38},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:38}},{type:"Twig.expression.type.string",value:"surface/metric",position:{start:0,end:38}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:38},expression:!1}]}]},{type:"raw",value:`

<div class="metric container`,position:{start:38,end:68}},{type:"output",position:{start:68,end:99},stack:[{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:68,end:99}},{type:"Twig.expression.type.string",value:" ",position:{start:68,end:99}},{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:68,end:99}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:68,end:99},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:68,end:99},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"output_whitespace_both",position:{start:99,end:142},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:99,end:142}},{type:"Twig.expression.type.string",value:" ",position:{start:99,end:142}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:99,end:142}},{type:"Twig.expression.type.key.period",position:{start:99,end:142},key:"class"},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:99,end:142},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:99,end:142},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:'"',position:{start:142,end:144}},{type:"output_whitespace_both",position:{start:144,end:190},stack:[{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:144,end:190}},{type:"Twig.expression.type.variable",value:"attributes",match:["attributes"],position:{start:144,end:190}},{type:"Twig.expression.type.filter",value:"without",match:["|without","without"],position:{start:144,end:190},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:144,end:190}},{type:"Twig.expression.type.variable",value:"class",match:["class"],position:{start:144,end:190}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:144,end:190},expression:!1}]},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:144,end:190},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`>
  <div class="metric__container">
    <div class="metric__title">
      `,position:{start:190,end:264}},{type:"output",position:{start:264,end:275},stack:[{type:"Twig.expression.type.variable",value:"title",match:["title"],position:{start:264,end:275}}]},{type:"raw",value:`
    </div>
    <div class="metric__subtitle">
      `,position:{start:275,end:328}},{type:"output",position:{start:328,end:342},stack:[{type:"Twig.expression.type.variable",value:"subtitle",match:["subtitle"],position:{start:328,end:342}}]},{type:"raw",value:`
    </div>
    <div class="metric__text prose">
      `,position:{start:342,end:397}},{type:"output",position:{start:397,end:407},stack:[{type:"Twig.expression.type.variable",value:"text",match:["text"],position:{start:397,end:407}}]},{type:"raw",value:`
    </div>
  </div>
</div>
`,position:{start:407,end:407}}],precompiled:!0});i.options.allowInlineIncludes=!0;try{return s(i.render({attributes:new f,...e}))}catch(v){return s("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/metric/metric.twig: "+v.toString())}},_={modifier:"",title:"99%",subtitle:"Metric Testing",text:"<p>Jelly-o carrot cake tootsie roll chocolate bar croissant macaroon pastry toffee apple pie.&nbsp;</p>"},k={title:"Components/Metric"},t={name:"Metric",render:e=>w(S(e)),args:{..._}},a={...t,name:"With Santa Barbara Sand background",decorators:[b]},r={...t,name:"With Venice Canal background",decorators:[h]};var o,n,p;t.parameters={...t.parameters,docs:{...(o=t.parameters)==null?void 0:o.docs,source:{originalSource:`{
  name: 'Metric',
  render: args => parse(metric(args)),
  args: {
    ...data
  }
}`,...(p=(n=t.parameters)==null?void 0:n.docs)==null?void 0:p.source}}};var c,d,l;a.parameters={...a.parameters,docs:{...(c=a.parameters)==null?void 0:c.docs,source:{originalSource:`{
  ...Metric,
  name: 'With Santa Barbara Sand background',
  decorators: [SantaBarbaraSandBg]
}`,...(l=(d=a.parameters)==null?void 0:d.docs)==null?void 0:l.source}}};var u,y,m;r.parameters={...r.parameters,docs:{...(u=r.parameters)==null?void 0:u.docs,source:{originalSource:`{
  ...Metric,
  name: 'With Venice Canal background',
  decorators: [VeniceCanalBg]
}`,...(m=(y=r.parameters)==null?void 0:y.docs)==null?void 0:m.source}}};const M=["Metric","SantaBarbaraSand","VeniceCanal"],P=Object.freeze(Object.defineProperty({__proto__:null,Metric:t,SantaBarbaraSand:a,VeniceCanal:r,__namedExportsOrder:M,default:k},Symbol.toStringTag,{value:"Module"}));export{P as M,a as S,r as V,t as a};
