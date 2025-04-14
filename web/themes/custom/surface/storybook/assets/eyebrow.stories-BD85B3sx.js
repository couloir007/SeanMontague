import{p as d}from"./index-C-ZRuDWU.js";import{e as m}from"./eyebrow-DtcqqXFq.js";const c={eyebrow:{modifier:"",text:"This is a label",url:"#"}},b={title:"Elements/Eyebrow",parameters:{controls:{disable:!0}}},e={name:"Plain text label",render:a=>d(m(a)),args:{...c,eyebrow:{modifier:"",text:"This is a plain text label",url:""}}},r={name:"Label as a link",render:a=>d(m(a)),args:{...c,eyebrow:{modifier:"some-class",text:"This is a label as a link",url:"https://medschool.ucla.edu"}}};var s,t,o;e.parameters={...e.parameters,docs:{...(s=e.parameters)==null?void 0:s.docs,source:{originalSource:`{
  name: 'Plain text label',
  render: args => parse(eyebrow(args)),
  args: {
    ...data,
    eyebrow: {
      modifier: '',
      text: 'This is a plain text label',
      url: ''
    }
  }
}`,...(o=(t=e.parameters)==null?void 0:t.docs)==null?void 0:o.source}}};var n,l,i;r.parameters={...r.parameters,docs:{...(n=r.parameters)==null?void 0:n.docs,source:{originalSource:`{
  name: 'Label as a link',
  render: args => parse(eyebrow(args)),
  args: {
    ...data,
    eyebrow: {
      modifier: 'some-class',
      text: 'This is a label as a link',
      url: 'https://medschool.ucla.edu'
    }
  }
}`,...(i=(l=r.parameters)==null?void 0:l.docs)==null?void 0:i.source}}};const p=["Eyebrow","Linked"],g=Object.freeze(Object.defineProperty({__proto__:null,Eyebrow:e,Linked:r,__namedExportsOrder:p,default:b},Symbol.toStringTag,{value:"Module"}));export{g as E,r as L,e as a};
