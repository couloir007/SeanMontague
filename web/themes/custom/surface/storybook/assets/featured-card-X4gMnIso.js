import{ae as e,af as l,ag as n}from"./index-B15VT6w7.js";import{useMDXComponents as d}from"./index-CcnH5Kt0.js";import{F as a,a as s,b as o}from"./featured-card.stories-CUl49LFu.js";import"./iframe-CGGnHX8c.js";import"../sb-preview/runtime.js";import"./index-RYns6xqu.js";import"./index-DAfSkmQi.js";import"./index-D-8MO0q_.js";import"./index-ar2LJKLv.js";import"./index-DrFu-skq.js";import"./index-C-ZRuDWU.js";import"./featured-card-CFieDTE8.js";import"./twig-CEFeP58X.js";import"./twig-D5Zl0i6Y.js";import"./title-ukWx3bcD.js";import"./date-DD8ziSoH.js";import"./button-Day_GlBX.js";import"./eyebrow-DtcqqXFq.js";import"./readtime-DWbwnKWq.js";function r(i){const t={code:"code",h1:"h1",h2:"h2",h3:"h3",li:"li",p:"p",ul:"ul",...d(),...i.components};return e.jsxs(e.Fragment,{children:[e.jsx(l,{of:a}),`
`,e.jsx(t.h1,{id:"featured-card",children:"Featured Card"}),`
`,e.jsx(t.p,{children:`The Featured content component highlights an event or article of choice when combined with other
events or articles in the Content highlight component. The Featured content component displays larger
than the other cards and uses a horizontal layout between the featured image and
the text. The Fatured content component is made up of the following
fields/variables:`}),`
`,e.jsx(t.h2,{id:"featured-card-article",children:"Featured Card Article"}),`
`,e.jsx(n,{of:s}),`
`,e.jsx(t.h2,{id:"featured-card-event",children:"Featured Card Event"}),`
`,e.jsx(n,{of:o}),`
`,e.jsx(t.h2,{id:"featured-card-technical-specs",children:"Featured Card technical specs"}),`
`,e.jsx(t.h3,{id:"combined-fieldsvariables-between-event-and-article",children:"Combined fields/variables between Event and Article"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"Featured media"})," (required): This is a 3:2 aspect ratio image that expands up to about 670px wide when viewed in full width mode."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"title"})," (required): The node title text which is linked to the full node page."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"subtitle"})," (optional): If a subtitle is added to the event when created, it will display below the title in smaller size."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"date"})," (required): The event date (next upcoming instance if multiple instances)."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"CTA"})," (required): A button (usually yellow), linked to the full event page."]}),`
`]}),`
`,e.jsx(t.h3,{id:"article-only-variablesfields",children:"Article only variables/fields"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"readtime"}),": Displays an approximate amount of time it takes to read the full article (i.e. 5 min read)."]}),`
`]}),`
`,e.jsx(t.h3,{id:"event-only-variablesfields",children:"Event only variables/fields"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"event_type"})," (required): A label that displays the type of event (i.e. In-Person, Hybrid, Virtual, etc.)."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"organization"})," (optional): This is the name of the room, building or venue where the event is taking place.  It is automatically linked to Google maps based on the address provided during event creation."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"more dates"}),": If the event has multiple instances, the label ",e.jsx(t.code,{children:"+ more dates"})," is shown next to the date and it is linked to the full event page."]}),`
`]})]})}function E(i={}){const{wrapper:t}={...d(),...i.components};return t?e.jsx(t,{...i,children:e.jsx(r,{...i})}):r(i)}export{E as default};
