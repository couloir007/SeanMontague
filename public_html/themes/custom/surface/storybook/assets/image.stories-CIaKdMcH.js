import{t as R,T as A}from"./twig-DVM_DCke.js";import{D as T,a as E}from"./twig-CTQ74SmP.js";E(A);A.cache(!1);const g=a=>a,O=(a={})=>{const i=R.twig({id:"/media/sean/0c49c450-91fc-4810-9e56-9c253853713d/Shared/www/SeanMontague/public_html/themes/custom/surface/source/patterns/elements/images/image.twig",data:[{type:"raw",value:`<figure>
  `,position:{start:0,end:11}},{type:"output",position:{start:11,end:22},stack:[{type:"Twig.expression.type.variable",value:"image",match:["image"],position:{start:11,end:22}}]},{type:"raw",value:`
</figure>
`,position:{start:22,end:22}}],precompiled:!0});i.options.allowInlineIncludes=!0;try{let r=a.defaultAttributes?a.defaultAttributes:[];return Array.isArray(r)||(r=Object.entries(r)),g(i.render({attributes:new T(r),...a}))}catch(r){return g("An error occurred whilst rendering /media/sean/0c49c450-91fc-4810-9e56-9c253853713d/Shared/www/SeanMontague/public_html/themes/custom/surface/source/patterns/elements/images/image.twig: "+r.toString())}},c={image:'<img src="/images/1-1.svg" alt="placeholder text" />'},j={title:"Elements/Images",parameters:{controls:{disable:!0}}},e={name:"Square",render:a=>O(a),args:{...c,image:'<img src="./images/1-1.svg" alt="placeholder text" />'}},t={...e,name:"2:3",args:{...c,image:'<img src="./images/2-3.svg" alt="placeholder text" />'}},s={...e,name:"3:2",args:{...c,image:'<img src="./images/3-2.svg" alt="placeholder text" />'}},m={...e,name:"4:3",args:{...c,image:'<img src="./images/4-3.svg" alt="placeholder text" />'}},o={...e,name:"16:9",args:{...c,image:'<img src="./images/16-9.svg" alt="placeholder text" />'}};var n,l,d;e.parameters={...e.parameters,docs:{...(n=e.parameters)==null?void 0:n.docs,source:{originalSource:`{
  name: 'Square',
  render: args => image(args),
  args: {
    ...data,
    image: '<img src="./images/1-1.svg" alt="placeholder text" />'
  }
}`,...(d=(l=e.parameters)==null?void 0:l.docs)==null?void 0:d.source}}};var u,p,h;t.parameters={...t.parameters,docs:{...(u=t.parameters)==null?void 0:u.docs,source:{originalSource:`{
  ...Image,
  name: '2:3',
  args: {
    ...data,
    image: '<img src="./images/2-3.svg" alt="placeholder text" />'
  }
}`,...(h=(p=t.parameters)==null?void 0:p.docs)==null?void 0:h.source}}};var f,w,v;s.parameters={...s.parameters,docs:{...(f=s.parameters)==null?void 0:f.docs,source:{originalSource:`{
  ...Image,
  name: '3:2',
  args: {
    ...data,
    image: '<img src="./images/3-2.svg" alt="placeholder text" />'
  }
}`,...(v=(w=s.parameters)==null?void 0:w.docs)==null?void 0:v.source}}};var S,b,x;m.parameters={...m.parameters,docs:{...(S=m.parameters)==null?void 0:S.docs,source:{originalSource:`{
  ...Image,
  name: '4:3',
  args: {
    ...data,
    image: '<img src="./images/4-3.svg" alt="placeholder text" />'
  }
}`,...(x=(b=m.parameters)==null?void 0:b.docs)==null?void 0:x.source}}};var I,y,_;o.parameters={...o.parameters,docs:{...(I=o.parameters)==null?void 0:I.docs,source:{originalSource:`{
  ...Image,
  name: '16:9',
  args: {
    ...data,
    image: '<img src="./images/16-9.svg" alt="placeholder text" />'
  }
}`,...(_=(y=o.parameters)==null?void 0:y.docs)==null?void 0:_.source}}};const D=["Image","Portrait","Rectangular32","Rectangular43","Rectangular169"],k=Object.freeze(Object.defineProperty({__proto__:null,Image:e,Portrait:t,Rectangular169:o,Rectangular32:s,Rectangular43:m,__namedExportsOrder:D,default:j},Symbol.toStringTag,{value:"Module"}));export{k as I,s as R,e as a,m as b,o as c};
