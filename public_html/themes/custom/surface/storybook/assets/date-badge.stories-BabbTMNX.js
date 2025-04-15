import{p as b}from"./index-C-ZRuDWU.js";import{w as B}from"./decorators-7nrNV5GJ.js";import{b as u}from"./date-badge-CPVOQVkw.js";const s={modifier:"",month:"Oct",day:"31"},f={title:"Elements/Date Badge",parameters:{controls:{include:["month","day"]}}},e={name:"Date badge",render:t=>b(u(t)),args:{...s}},a={name:"Date badge",render:t=>b(u(t)),args:{...s,modifier:"date-badge--small"}},r={...e,name:"Light date badge",args:{...s,modifier:"date-badge--light"},decorators:[B]};var d,o,n;e.parameters={...e.parameters,docs:{...(d=e.parameters)==null?void 0:d.docs,source:{originalSource:`{
  name: 'Date badge',
  render: args => parse(badge(args)),
  args: {
    ...data
  }
}`,...(n=(o=e.parameters)==null?void 0:o.docs)==null?void 0:n.source}}};var g,m,c;a.parameters={...a.parameters,docs:{...(g=a.parameters)==null?void 0:g.docs,source:{originalSource:`{
  name: 'Date badge',
  render: args => parse(badge(args)),
  args: {
    ...data,
    modifier: 'date-badge--small'
  }
}`,...(c=(m=a.parameters)==null?void 0:m.docs)==null?void 0:c.source}}};var i,p,l;r.parameters={...r.parameters,docs:{...(i=r.parameters)==null?void 0:i.docs,source:{originalSource:`{
  ...Badge,
  name: 'Light date badge',
  args: {
    ...data,
    modifier: 'date-badge--light'
  },
  decorators: [withBackground]
}`,...(l=(p=r.parameters)==null?void 0:p.docs)==null?void 0:l.source}}};const h=["Badge","BadgeSmall","BadgeLight"],y=Object.freeze(Object.defineProperty({__proto__:null,Badge:e,BadgeLight:r,BadgeSmall:a,__namedExportsOrder:h,default:f},Symbol.toStringTag,{value:"Module"}));export{e as B,y as D,a,r as b};
