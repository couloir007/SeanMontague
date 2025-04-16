import{p as l}from"./index-C-ZRuDWU.js";import{T as o,t as d}from"./twig-CEFeP58X.js";import{a as c,D as u}from"./twig-D5Zl0i6Y.js";c(o);o.cache(!1);const s=e=>e,y=(e={})=>{const a=d.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/share/share.twig",data:[{type:"output",position:{start:0,end:37},stack:[{type:"Twig.expression.type._function",position:{start:0,end:37},fn:"attach_library",params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:0,end:37}},{type:"Twig.expression.type.string",value:"surface/share",position:{start:0,end:37}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:0,end:37},expression:!1}]}]},{type:"raw",value:`

<div class="share`,position:{start:37,end:56}},{type:"output_whitespace_both",position:{start:56,end:78},stack:[{type:"Twig.expression.type.string",value:" ",position:{start:56,end:78}},{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:56,end:78}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:56,end:78},precidence:6,associativity:"leftToRight",operator:"~"}]},{type:"raw",value:`">
  <div>Share:</div>
  <ul>
    <li>
      <a title="Share this article on Facebook" href="`,position:{start:78,end:171}},{type:"output",position:{start:171,end:206},stack:[{type:"Twig.expression.type.variable",value:"article_facebook",match:["article_facebook"],position:{start:171,end:206}},{type:"Twig.expression.type.filter",value:"default",match:["|default","default"],position:{start:171,end:206},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:171,end:206}},{type:"Twig.expression.type.string",value:"#",position:{start:171,end:206}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:171,end:206},expression:!1}]}]},{type:"raw",value:`" target="_blank">
        <span class="fa-brands fa-facebook"></span>
        <span class="visually-hidden">Facebook</span>
      </a>
    </li>
    <li>
      <a title="Share this article on Twitter" href="`,position:{start:206,end:414}},{type:"output",position:{start:414,end:448},stack:[{type:"Twig.expression.type.variable",value:"article_twitter",match:["article_twitter"],position:{start:414,end:448}},{type:"Twig.expression.type.filter",value:"default",match:["|default","default"],position:{start:414,end:448},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:414,end:448}},{type:"Twig.expression.type.string",value:"#",position:{start:414,end:448}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:414,end:448},expression:!1}]}]},{type:"raw",value:`" target="_blank">
        <span class="fa-brands fa-x-twitter"></span>
        <span class="visually-hidden">Twitter</span>
      </a>
    </li>
    <li>
      <a title="Share this article on LinkedIn" href="`,position:{start:448,end:657}},{type:"output",position:{start:657,end:692},stack:[{type:"Twig.expression.type.variable",value:"article_linkedin",match:["article_linkedin"],position:{start:657,end:692}},{type:"Twig.expression.type.filter",value:"default",match:["|default","default"],position:{start:657,end:692},params:[{type:"Twig.expression.type.parameter.start",value:"(",match:["("],position:{start:657,end:692}},{type:"Twig.expression.type.string",value:"#",position:{start:657,end:692}},{type:"Twig.expression.type.parameter.end",value:")",match:[")"],position:{start:657,end:692},expression:!1}]}]},{type:"raw",value:`" target="_blank">
        <span class="fa-brands fa-linkedin-in"></span>
        <span class="visually-hidden">LinkedIn</span>
      </a>
    </li>
  </ul>
</div>
`,position:{start:692,end:692}}],precompiled:!0});a.options.allowInlineIncludes=!0;try{return s(a.render({attributes:new u,...e}))}catch(p){return s("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/components/share/share.twig: "+p.toString())}},m={title:"Components/Share"},t={name:"Share",render:e=>l(y(e))};var r,n,i;t.parameters={...t.parameters,docs:{...(r=t.parameters)==null?void 0:r.docs,source:{originalSource:`{
  name: 'Share',
  render: args => parse(share(args))
}`,...(i=(n=t.parameters)==null?void 0:n.docs)==null?void 0:i.source}}};const h=["Stacked"],v=Object.freeze(Object.defineProperty({__proto__:null,Stacked:t,__namedExportsOrder:h,default:m},Symbol.toStringTag,{value:"Module"}));export{v as S,t as a};
