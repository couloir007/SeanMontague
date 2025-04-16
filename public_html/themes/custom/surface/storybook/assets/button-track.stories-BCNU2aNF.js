import{p as d}from"./index-C-ZRuDWU.js";import{b as u}from"./button-track-BskoRuOl.js";const m={action:"Download",category:"Video",label:"Download a video",icon:"",modifier:"button--primary",text:"Tracking button",url:"#"},p={title:"Elements/Button track",parameters:{controls:{include:["text"]}}},r={render:a=>d(u(a)),args:{...m}},t={render:a=>d(u(a)),name:"Button with icon",args:{...m,icon:"fa-solid fa-arrow-right"}};var o,e,n;r.parameters={...r.parameters,docs:{...(o=r.parameters)==null?void 0:o.docs,source:{originalSource:`{
  render: args => parse(button(args)),
  args: {
    ...data
  }
}`,...(n=(e=r.parameters)==null?void 0:e.docs)==null?void 0:n.source}}};var s,c,i;t.parameters={...t.parameters,docs:{...(s=t.parameters)==null?void 0:s.docs,source:{originalSource:`{
  render: args => parse(button(args)),
  name: 'Button with icon',
  args: {
    ...data,
    icon: 'fa-solid fa-arrow-right'
  }
}`,...(i=(c=t.parameters)==null?void 0:c.docs)==null?void 0:i.source}}};const l=["ButtonTrack","ButtonTrackIcon"],f=Object.freeze(Object.defineProperty({__proto__:null,ButtonTrack:r,ButtonTrackIcon:t,__namedExportsOrder:l,default:p},Symbol.toStringTag,{value:"Module"}));export{f as B,r as a,t as b};
