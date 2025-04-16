import{p as l}from"./index-C-ZRuDWU.js";import{f as c}from"./featured-card-CFieDTE8.js";const m={modifier:"",image:'<img src="images/3-2.svg" alt="placeholder text" width="768" height="512" />',title:{modifier:"featured-card__title",level:3,text:"Amendment and Addendum HS 9415",url:"#"},subtitle:{modifier:"featured-card__subtitle",level:4,text:"Join the HIMS Director and Management Team for updates on patient record integrity.",url:""},date:{modifier:"",date:"October 31, 2023 | 12:30 PM – 1:30 PM"},cta:{modifier:"button--secondary",text:"See the full event",url:"#"},event_type:{modifier:"featured-card__type",text:"In-Person",url:""},more_dates:!0,organization:'<a href="#">Davig Geffen School of Medicine</a>',readtime:""},u={modifier:"",image:'<img src="images/3-2.svg" alt="placeholder text" width="768" height="512" />',title:{modifier:"featured-card__title",level:3,text:"UCLA Health HIMS Forum",url:"#"},subtitle:{modifier:"featured-card__subtitle",level:4,text:"Join the HIMS Director and Management Team for updates on patient record integrity.",url:""},date:{modifier:"",date:"October 31, 2023 | 12:30 PM – 1:30 PM"},cta:{modifier:"button--secondary",text:"Read the full article",url:"#"},event_type:"",more_dates:!1,organization:"David Geffen School of Medicine",readtime:"5 min read"},f={title:"Components/Featured Card"},e={name:"Featured Article",render:r=>l(c(r)),args:{...u}},t={name:"Featured Event",render:r=>l(c(r)),args:{...m}};var a,d,i;e.parameters={...e.parameters,docs:{...(a=e.parameters)==null?void 0:a.docs,source:{originalSource:`{
  name: 'Featured Article',
  render: args => parse(featuredCard(args)),
  args: {
    ...dataArticle
  }
}`,...(i=(d=e.parameters)==null?void 0:d.docs)==null?void 0:i.source}}};var o,n,s;t.parameters={...t.parameters,docs:{...(o=t.parameters)==null?void 0:o.docs,source:{originalSource:`{
  name: 'Featured Event',
  render: args => parse(featuredCard(args)),
  args: {
    ...dataEvent
  }
}`,...(s=(n=t.parameters)==null?void 0:n.docs)==null?void 0:s.source}}};const g=["FeaturedArticle","FeaturedEvent"],v=Object.freeze(Object.defineProperty({__proto__:null,FeaturedArticle:e,FeaturedEvent:t,__namedExportsOrder:g,default:f},Symbol.toStringTag,{value:"Module"}));export{v as F,e as a,t as b};
