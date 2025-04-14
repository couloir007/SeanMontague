import{p}from"./index-C-ZRuDWU.js";import{c as m}from"./card-BxeYgWss.js";const t={modifier:"",type:"",image:'<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/3_2_large/public/2024-05/EarthComparison.png.webp" alt="placeholder text" />',event_type:"Overview",title:{level:3,modifier:"content-card__title",text:"Observing the World’s Ocean from Space"},by_line:"Sean Montague",by_line_url:"https://dev-oceanportal.pantheonsite.io/contributors/madison-goldberg",url:"https://medschool.ucla.edu",description:"Most marine fish larvae tend to live in surface or near-surface waters, while adult fish inhabit largely different environments. The different habitats require two different body shapes, leading to larvae that look very different from their adult counterparts."},G={title:"Components/Card"},e={name:"Basic card",render:a=>p(m(a)),args:{...t,modifier:"card--basic"}},r={...e,name:"Spotlight Card Big",args:{...t,event_type:"",by_line:"",description:"",modifier:"card--spotlight",image:'<img src="https://oceanportal.lndo.site/sites/default/files/styles/spotlight_big/public/2024-11/Swiftia_exserta_spawning_image_CREDIT_NOAA_USGS.jpg.webp" alt="placeholder text" />'}},i={...e,name:"Spotlight Card Small",args:{...t,event_type:"",by_line:"",description:"",modifier:"card--spotlight card--spotlight_small",image:'<img src="https://oceanportal.lndo.site/sites/default/files/styles/spotlight_small/public/2024-10/jpegPIA25621.jpg.webp" />',title:{level:2,modifier:"content-card__title",text:"Anglerfish Lure Prey Throughout the Ocean"}}},s={...e,name:"QuickLinks Card",args:{...t,event_type:"",by_line:"",description:"",modifier:"card--spotlight card--quicklinks",image:'<img src="https://oceanportal.lndo.site/sites/default/files/styles/3_4_portrait_large/public/2018-05/Sharks_QL.jpg.webp" />',title:{level:3,modifier:"content-card__title",text:"Hurricanes Typhoons & Cyclones"}}},o={...e,name:"Featured Card",args:{...t,event_type:"",description:"",modifier:"card--featured-content",image:'<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/spotlight_big/public/2024-10/jpegPIA25621.jpg.webp" alt="placeholder text" />'}},l={...e,name:"Daily Catch Card",args:{...t,event_type:"",by_line:"",modifier:"",image:'<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/spotlight_big/public/2024-10/jpegPIA25621.jpg.webp" alt="placeholder text" />'}},c={...e,name:"Featured Topic Card",args:{...t,title:{level:2,modifier:"content-card__title",text:"Bioluminescence Overview"},cta:{action:"Read",category:"Article",label:"Read Article",modifier:"button--primary",text:"Read More",url:"https://ocean.si.edu/ocean-life/fish/bioluminescence"},no_link:"featured_topic",event_type:"",by_line:"",modifier:"card--featured_topic",description:"For some ocean creatures, creating light is a matter of life and death. Learn about how light is used in the ocean.",image:'<img src="https://oceanportal.lndo.site/sites/default/files/styles/3_2_large/public/2023-11/2_09_P08_G10_Abraliabigger.jpg.webp" alt="placeholder text" />'}},d={name:"Featured Topic Content Card",render:a=>p(m(a)),args:{...t,title:{level:2,modifier:"content-card__title",text:"Cephelopods"},modifier:"card--featured-content",by_line:"",description:"",image:'<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/3_2_small/public/2023-11/Ocotpuses.jpg.webp" alt="placeholder text" />'}},n={name:"Search Card",render:a=>p(m(a)),args:{...t,title:{level:2,modifier:"content-card__title",text:"Cephelopods"},by_line:"",description:"When we think of whales, the enormous ones that filter tiny plankton from seawater...",modifier:"card__search",image:'<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/3_2_large/public/2023-11/Ocotpuses.jpg.webp" alt="placeholder text" />',date_short:{modifier:"date-badge--small",month:"Oct",year:"2020"}}};var g,u,h;e.parameters={...e.parameters,docs:{...(g=e.parameters)==null?void 0:g.docs,source:{originalSource:`{
  name: 'Basic card',
  render: args => parse(card(args)),
  args: {
    ...data,
    modifier: 'card--basic'
  }
}`,...(h=(u=e.parameters)==null?void 0:u.docs)==null?void 0:h.source}}};var _,f,b;r.parameters={...r.parameters,docs:{...(_=r.parameters)==null?void 0:_.docs,source:{originalSource:`{
  ...BasicCard,
  name: 'Spotlight Card Big',
  args: {
    ...data,
    event_type: '',
    by_line: '',
    description: '',
    modifier: 'card--spotlight',
    image: '<img src="https://oceanportal.lndo.site/sites/default/files/styles/spotlight_big/public/2024-11/Swiftia_exserta_spawning_image_CREDIT_NOAA_USGS.jpg.webp" alt="placeholder text" />'
  }
}`,...(b=(f=r.parameters)==null?void 0:f.docs)==null?void 0:b.source}}};var y,C,v;i.parameters={...i.parameters,docs:{...(y=i.parameters)==null?void 0:y.docs,source:{originalSource:`{
  ...BasicCard,
  name: 'Spotlight Card Small',
  args: {
    ...data,
    event_type: '',
    by_line: '',
    description: '',
    modifier: 'card--spotlight card--spotlight_small',
    image: '<img src="https://oceanportal.lndo.site/sites/default/files/styles/spotlight_small/public/2024-10/jpegPIA25621.jpg.webp" />',
    title: {
      level: 2,
      modifier: 'content-card__title',
      text: 'Anglerfish Lure Prey Throughout the Ocean'
    }
  }
}`,...(v=(C=i.parameters)==null?void 0:C.docs)==null?void 0:v.source}}};var S,w,x;s.parameters={...s.parameters,docs:{...(S=s.parameters)==null?void 0:S.docs,source:{originalSource:`{
  ...BasicCard,
  name: 'QuickLinks Card',
  args: {
    ...data,
    event_type: '',
    by_line: '',
    description: '',
    modifier: 'card--spotlight card--quicklinks',
    image: '<img src="https://oceanportal.lndo.site/sites/default/files/styles/3_4_portrait_large/public/2018-05/Sharks_QL.jpg.webp" />',
    title: {
      level: 3,
      modifier: 'content-card__title',
      text: 'Hurricanes Typhoons & Cyclones'
    }
  }
}`,...(x=(w=s.parameters)==null?void 0:w.docs)==null?void 0:x.source}}};var j,k,A;o.parameters={...o.parameters,docs:{...(j=o.parameters)==null?void 0:j.docs,source:{originalSource:`{
  ...BasicCard,
  name: 'Featured Card',
  args: {
    ...data,
    event_type: '',
    description: '',
    modifier: 'card--featured-content',
    image: '<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/spotlight_big/public/2024-10/jpegPIA25621.jpg.webp" alt="placeholder text" />'
  }
}`,...(A=(k=o.parameters)==null?void 0:k.docs)==null?void 0:A.source}}};var O,B,T;l.parameters={...l.parameters,docs:{...(O=l.parameters)==null?void 0:O.docs,source:{originalSource:`{
  ...BasicCard,
  name: 'Daily Catch Card',
  args: {
    ...data,
    event_type: '',
    by_line: '',
    modifier: '',
    image: '<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/spotlight_big/public/2024-10/jpegPIA25621.jpg.webp" alt="placeholder text" />'
  }
}`,...(T=(B=l.parameters)==null?void 0:B.docs)==null?void 0:T.source}}};var F,P,L;c.parameters={...c.parameters,docs:{...(F=c.parameters)==null?void 0:F.docs,source:{originalSource:`{
  ...BasicCard,
  name: 'Featured Topic Card',
  args: {
    ...data,
    title: {
      level: 2,
      modifier: 'content-card__title',
      text: 'Bioluminescence Overview'
    },
    cta: {
      action: 'Read',
      category: 'Article',
      label: 'Read Article',
      modifier: 'button--primary',
      text: 'Read More',
      url: 'https://ocean.si.edu/ocean-life/fish/bioluminescence'
    },
    no_link: 'featured_topic',
    event_type: '',
    by_line: '',
    modifier: 'card--featured_topic',
    description: 'For some ocean creatures, creating light is a matter of life and death. Learn about how light is used in the ocean.',
    image: '<img src="https://oceanportal.lndo.site/sites/default/files/styles/3_2_large/public/2023-11/2_09_P08_G10_Abraliabigger.jpg.webp" alt="placeholder text" />'
  }
}`,...(L=(P=c.parameters)==null?void 0:P.docs)==null?void 0:L.source}}};var I,R,D;d.parameters={...d.parameters,docs:{...(I=d.parameters)==null?void 0:I.docs,source:{originalSource:`{
  name: 'Featured Topic Content Card',
  render: args => parse(card(args)),
  args: {
    ...data,
    title: {
      level: 2,
      modifier: 'content-card__title',
      text: 'Cephelopods'
    },
    modifier: 'card--featured-content',
    by_line: '',
    description: '',
    image: '<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/3_2_small/public/2023-11/Ocotpuses.jpg.webp" alt="placeholder text" />'
  }
}`,...(D=(R=d.parameters)==null?void 0:R.docs)==null?void 0:D.source}}};var Q,M,E;n.parameters={...n.parameters,docs:{...(Q=n.parameters)==null?void 0:Q.docs,source:{originalSource:`{
  name: 'Search Card',
  render: args => parse(card(args)),
  args: {
    ...data,
    title: {
      level: 2,
      modifier: 'content-card__title',
      text: 'Cephelopods'
    },
    by_line: '',
    description: 'When we think of whales, the enormous ones that filter tiny plankton from seawater...',
    modifier: 'card__search',
    image: '<img src="https://dev-oceanportal.pantheonsite.io/sites/default/files/styles/3_2_large/public/2023-11/Ocotpuses.jpg.webp" alt="placeholder text" />',
    date_short: {
      modifier: 'date-badge--small',
      month: 'Oct',
      year: '2020'
    }
  }
}`,...(E=(M=n.parameters)==null?void 0:M.docs)==null?void 0:E.source}}};const q=["BasicCard","SpotlightCardBig","SpotlightCardSmall","QuickLinksCard","FeaturedCard","DailyCatchCard","FeaturedTopicCard","FeaturedTopicContentCard","SearchCard"],N=Object.freeze(Object.defineProperty({__proto__:null,BasicCard:e,DailyCatchCard:l,FeaturedCard:o,FeaturedTopicCard:c,FeaturedTopicContentCard:d,QuickLinksCard:s,SearchCard:n,SpotlightCardBig:r,SpotlightCardSmall:i,__namedExportsOrder:q,default:G},Symbol.toStringTag,{value:"Module"}));export{e as B,N as C,r as S,i as a};
