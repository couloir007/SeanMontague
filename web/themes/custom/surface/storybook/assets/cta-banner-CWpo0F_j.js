import{ae as e,af as s,ag as r}from"./index-B15VT6w7.js";import{useMDXComponents as i}from"./index-CcnH5Kt0.js";import{C as a,a as l,b as c}from"./cta-banner.stories-DxFh_2oE.js";import"./iframe-CGGnHX8c.js";import"../sb-preview/runtime.js";import"./index-RYns6xqu.js";import"./index-DAfSkmQi.js";import"./index-D-8MO0q_.js";import"./index-ar2LJKLv.js";import"./index-DrFu-skq.js";import"./index-C-ZRuDWU.js";import"./twig-CEFeP58X.js";import"./twig-D5Zl0i6Y.js";import"./title-ukWx3bcD.js";import"./button-track-BskoRuOl.js";import"./decorators-7nrNV5GJ.js";function o(n){const t={a:"a",code:"code",h1:"h1",h2:"h2",li:"li",ol:"ol",p:"p",ul:"ul",...i(),...n.components};return e.jsxs(e.Fragment,{children:[e.jsx(s,{of:a}),`
`,e.jsx(t.h1,{id:"cta-banner",children:"CTA Banner"}),`
`,e.jsx(t.p,{children:`The CTA Banner component is used as a Call to Action for Qualtrics or Matterport reports.  It is very unique
in that it only allows links that contain the domains qualtrics.com and matterport.com.  Depending on the domain used, a decorative image is added to the component when rendered in Drupal.`}),`
`,e.jsx(t.h2,{id:"matterport-banner",children:"Matterport Banner"}),`
`,e.jsx(r,{of:l}),`
`,e.jsx(t.h2,{id:"qualtrics-banner",children:"Qualtrics Banner"}),`
`,e.jsx(r,{of:c}),`
`,e.jsx(t.h2,{id:"cta-banner-technical-specs",children:"CTA Banner technical specs"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"title"})," (required): The title of the CTA content"]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"teaser"})," (required): A short summary of text."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"link"})," (required): The link/url field has been configured to only allow links with either qualtrics.com or metaport.com domains. All other links are not allowed."]}),`
`]}),`
`,e.jsx(t.h2,{id:"layout-options",children:"Layout options"}),`
`,e.jsx(t.p,{children:`The CTA Banner by defaul is meant to be added to a one or two column page layout, however,
it also allows the content creators to select whether the component is being added to a narrow
area by selecting from the Layout options in the paragraph type. When added to a narrow section the
component's styles adapt to ensure the component is rendered in a presentable manner.`}),`
`,e.jsx(t.h2,{id:"developerdrupal-customizations",children:"Developer/Drupal Customizations"}),`
`,e.jsx(t.p,{children:"The field_sf_link includes a url and a url text. One of the requirements is to only allow links from qualtrics.com or matterport.com. All other links internal or external, are not allowed. This customization is dependent on this fields help text."}),`
`,e.jsxs(t.ol,{children:[`
`,e.jsxs(t.li,{children:[`
`,e.jsx(t.p,{children:"Help text for field_sf_link now reads:"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[`
`,e.jsx(t.p,{children:"Include a short description of the page or resource you are linking to in Qualtrics or Matterport. Avoid using generic phrases like “Click here” or “Read More.”"}),`
`]}),`
`,e.jsxs(t.li,{children:[`
`,e.jsxs(t.p,{children:["Help text can be changed but must have the string ",e.jsx(t.code,{children:"Qualtrics or Matterport"})," as a substring of the help text."]}),`
`]}),`
`]}),`
`]}),`
`,e.jsxs(t.li,{children:[`
`,e.jsx(t.p,{children:"uclahs_custom.module is update to include a hook_form_alter that targets the layout_paragraph_component_form, checks to see if field_sf_link exits and if Qualtrics or Matterport is in the description of the help text, then change the uri help text to something more useful for the end user."}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:["The allowed URL forms are from Qualtrics (",e.jsx(t.a,{href:"http://qualtrics.com/",title:"http://qualtrics.com",rel:"nofollow",children:"qualtrics.com"}),") and Matterport (my.matterport.com)."]}),`
`]}),`
`]}),`
`]})]})}function A(n={}){const{wrapper:t}={...i(),...n.components};return t?e.jsx(t,{...n,children:e.jsx(o,{...n})}):o(n)}export{A as default};
