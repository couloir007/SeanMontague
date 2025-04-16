import{p as a}from"./index-C-ZRuDWU.js";import{h as s}from"./hero-gXI8uY7w.js";const o={modifier:"",attributes:"",image:'<img src="https://ocean.si.edu/sites/default/files/styles/banner_image/public/2018-05/OceanLife.jpg.webp" alt="Placeholder alternative text">',title:{level:1,modifier:"hero__title",text:"Ocean Life"},scientific_name:""},u={title:"Collections/Hero"},e={name:"Hero default",render:r=>a(s(r)),args:{...o}},i={...e,name:"Hero banner",render:r=>a(s(r)),args:{...o,modifier:"hero--banner",image:'<img src="https://ocean.si.edu/sites/default/files/styles/overview_hero/public/2023-11/PhilColla_AdeliePenguins.jpg.webp" alt="Placeholder alternative text">',title:{level:1,modifier:"hero__title",text:"Penguins"},scientific_name:"order Sphenisciformes, family Spheniscidae"}},t={...e,name:"Hero Timeline",render:r=>a(s(r)),args:{...o,modifier:"hero--timeline",image:'<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/timeline_header/public/2021-08/waves.jpg.webp" alt="Placeholder alternative text">',title:{level:1,modifier:"hero__title",text:"An Irrepressible Wave"},scientific_name:"A Timeline of the History of Women in Ocean Science"}};var n,l,c;e.parameters={...e.parameters,docs:{...(n=e.parameters)==null?void 0:n.docs,source:{originalSource:`{
  name: 'Hero default',
  render: args => parse(hero(args)),
  args: {
    ...data
  }
}`,...(c=(l=e.parameters)==null?void 0:l.docs)==null?void 0:c.source}}};var m,d,p;i.parameters={...i.parameters,docs:{...(m=i.parameters)==null?void 0:m.docs,source:{originalSource:`{
  ...HeroDefault,
  name: 'Hero banner',
  render: args => parse(hero(args)),
  args: {
    ...data,
    modifier: 'hero--banner',
    image: '<img src="https://ocean.si.edu/sites/default/files/styles/overview_hero/public/2023-11/PhilColla_AdeliePenguins.jpg.webp" alt="Placeholder alternative text">',
    title: {
      level: 1,
      modifier: 'hero__title',
      text: 'Penguins'
    },
    scientific_name: 'order Sphenisciformes, family Spheniscidae'
  }
}`,...(p=(d=i.parameters)==null?void 0:d.docs)==null?void 0:p.source}}};var f,g,h;t.parameters={...t.parameters,docs:{...(f=t.parameters)==null?void 0:f.docs,source:{originalSource:`{
  ...HeroDefault,
  name: 'Hero Timeline',
  render: args => parse(hero(args)),
  args: {
    ...data,
    modifier: 'hero--timeline',
    image: '<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/timeline_header/public/2021-08/waves.jpg.webp" alt="Placeholder alternative text">',
    title: {
      level: 1,
      modifier: 'hero__title',
      text: 'An Irrepressible Wave'
    },
    scientific_name: 'A Timeline of the History of Women in Ocean Science'
  }
}`,...(h=(g=t.parameters)==null?void 0:g.docs)==null?void 0:h.source}}};const _=["HeroDefault","HeroBanner","HeroTimeLine"],H=Object.freeze(Object.defineProperty({__proto__:null,HeroBanner:i,HeroDefault:e,HeroTimeLine:t,__namedExportsOrder:_,default:u},Symbol.toStringTag,{value:"Module"}));export{H,e as a,i as b};
