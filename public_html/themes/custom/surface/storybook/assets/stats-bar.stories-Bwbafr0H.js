import{s as o}from"./stats-bar-C-VxRTMC.js";import"./twig-DVM_DCke.js";import"./_commonjsHelpers-D6-XlEtG.js";import"./twig-CTQ74SmP.js";const c={stats:[{label:"Distance",value:"12.7",unit:"miles"},{label:"Elevation",value:"2,600",unit:"ft gain"},{label:"Est. Time",value:"2.5–3",unit:"hours"},{label:"Difficulty",value:"Intermediate",unit:"blue / some black",is_small:!0},{label:"Season",value:"May–Oct",unit:"check conditions",is_small:!0}]},b={title:"Components/Stats Bar"},a={render:t=>o(t),args:{...c}},e={render:t=>o(t),args:{stats:[{label:"Distance",value:"8.4",unit:"miles"},{label:"Gain",value:"1,200",unit:"ft"},{label:"Difficulty",value:"Easy",unit:"green"}]}};var s,n,r;a.parameters={...a.parameters,docs:{...(s=a.parameters)==null?void 0:s.docs,source:{originalSource:`{
  render: args => statsBar(args),
  args: {
    ...data
  }
}`,...(r=(n=a.parameters)==null?void 0:n.docs)==null?void 0:r.source}}};var l,i,u;e.parameters={...e.parameters,docs:{...(l=e.parameters)==null?void 0:l.docs,source:{originalSource:`{
  render: args => statsBar(args),
  args: {
    stats: [{
      label: 'Distance',
      value: '8.4',
      unit: 'miles'
    }, {
      label: 'Gain',
      value: '1,200',
      unit: 'ft'
    }, {
      label: 'Difficulty',
      value: 'Easy',
      unit: 'green'
    }]
  }
}`,...(u=(i=e.parameters)==null?void 0:i.docs)==null?void 0:u.source}}};const f=["Default","ThreeStat"];export{a as Default,e as ThreeStat,f as __namedExportsOrder,b as default};
