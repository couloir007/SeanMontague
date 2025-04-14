import{ae as e,af as o,ag as n}from"./index-B15VT6w7.js";import{useMDXComponents as r}from"./index-CcnH5Kt0.js";import{C as a,B as h,S as l,a as d}from"./card.stories-KV7jR8DZ.js";import"./iframe-CGGnHX8c.js";import"../sb-preview/runtime.js";import"./index-RYns6xqu.js";import"./index-DAfSkmQi.js";import"./index-D-8MO0q_.js";import"./index-ar2LJKLv.js";import"./index-DrFu-skq.js";import"./index-C-ZRuDWU.js";import"./card-BxeYgWss.js";import"./twig-CEFeP58X.js";import"./twig-D5Zl0i6Y.js";import"./eyebrow-DtcqqXFq.js";import"./title-ukWx3bcD.js";import"./button-track-BskoRuOl.js";import"./date-badge-CPVOQVkw.js";function s(i){const t={code:"code",h1:"h1",h2:"h2",h3:"h3",li:"li",p:"p",strong:"strong",ul:"ul",...r(),...i.components};return e.jsxs(e.Fragment,{children:[e.jsx(o,{of:a}),`
`,e.jsx(t.h1,{id:"card",children:"Card"}),`
`,e.jsxs(t.p,{children:[`The card component is a compact display option for Articles and Events content.
You will find the cards grouped within other components such as Content Highlight
and Related Articles/Events. The Card's max width is about 336px.
The card component is NOT to be used in Events landing pages or search pages.  There
is a specific component for that called `,e.jsx(t.strong,{children:"Event card"}),", which uses different display attributes."]}),`
`,e.jsx(t.h2,{id:"card---basic",children:"Card - Basic"}),`
`,e.jsxs(t.p,{children:[`The Event story for the card has a unique display that differentiates it from the Article card.
The short date format is placed at the top-left corner of the image using the date badge element.
It also includes the organization name and if the event contains multiple instances, the label
`,e.jsx(t.strong,{children:"+ more dates"})," is displayed which links to the full event node."]}),`
`,e.jsx(n,{of:h}),`
`,e.jsx(t.h2,{id:"card---spotlight-big",children:"Card - Spotlight Big"}),`
`,e.jsxs(t.p,{children:[`The Article story of the card while is similar to the event, does not have as many fields. The one field that
is unique to the article is the `,e.jsx(t.strong,{children:"readtime"})," which gives an estimated time that it takes to read the article."]}),`
`,e.jsx(n,{of:l}),`
`,e.jsx(t.h2,{id:"card---spotlight-small",children:"Card - Spotlight Small"}),`
`,e.jsxs(t.p,{children:[`The Article story of the card while is similar to the event, does not have as many fields. The one field that
is unique to the article is the `,e.jsx(t.strong,{children:"readtime"})," which gives an estimated time that it takes to read the article."]}),`
`,e.jsx(n,{of:d}),`
`,e.jsx(t.h2,{id:"card-technical-specs",children:"Card technical specs"}),`
`,e.jsx(t.p,{children:`Since a single source of thruth for markup controls both, Event and Article cards, some of the fields below
apply to both cards while others only to one of them.`}),`
`,e.jsx(t.h3,{id:"event-only-fields",children:"Event only fields"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"event_type"})," (required): A label that displays the type of event (i.e. In-Person, Hybrid, Virtual, etc.)."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"by_line"})," (optional): This author."]}),`
`]}),`
`,e.jsx(t.h3,{id:"article-only-fields",children:"Article only fields"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"readtime"})," (required): Displays an approximate amount of time it takes to read the article (i.e. 5 min read)."]}),`
`]})]})}function q(i={}){const{wrapper:t}={...r(),...i.components};return t?e.jsx(t,{...i,children:e.jsx(s,{...i})}):s(i)}export{q as default};
