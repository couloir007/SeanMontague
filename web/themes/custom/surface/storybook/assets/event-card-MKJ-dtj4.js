import{ae as e,af as o,ag as r}from"./index-B15VT6w7.js";import{useMDXComponents as s}from"./index-CcnH5Kt0.js";import{E as d,C as a}from"./event-card.stories-DpZo5IHh.js";import"./iframe-CGGnHX8c.js";import"../sb-preview/runtime.js";import"./index-RYns6xqu.js";import"./index-DAfSkmQi.js";import"./index-D-8MO0q_.js";import"./index-ar2LJKLv.js";import"./index-DrFu-skq.js";import"./index-C-ZRuDWU.js";import"./twig-CEFeP58X.js";import"./twig-D5Zl0i6Y.js";import"./date-DD8ziSoH.js";import"./eyebrow-DtcqqXFq.js";import"./title-ukWx3bcD.js";import"./date-badge-CPVOQVkw.js";import"./button-Day_GlBX.js";function i(n){const t={code:"code",h1:"h1",h2:"h2",li:"li",p:"p",ul:"ul",...s(),...n.components};return e.jsxs(e.Fragment,{children:[e.jsx(o,{of:d}),`
`,e.jsx(t.h1,{id:"event-card",children:"Event card"}),`
`,e.jsx(t.p,{children:`The Event card is a unique component used only in search results pages as well
as events landing pages.  The event card component is NOT to be used for
Event highlights or related events displays, there is another specific component for
those displays called Content card.`}),`
`,e.jsx(r,{of:a}),`
`,e.jsx(t.h2,{id:"event-card-technical-specs",children:"Event card technical specs"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"event_type"})," (required): A label that displays the type of event (i.e. In-Person, Hybrid, Virtual, etc.)."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"title"})," (required): The node title text which is linked to the full node page."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"meta_description"})," (optional): If a text is added to the meta_description field in an event, it will display below the title in smaller text size."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"organization"})," (optional): This is the name of the room, building or venue where the event is taking place.  It is automatically linked to Google maps based on the address provided during event creation."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"date_long"})," (required): The event date (next upcoming instance if multiple instances)."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"date_short_month"})," & ",e.jsx(t.code,{children:"date_short_day"})," (required): These are two date variations that allow for the next event instance to be displayed in short format and large text size.  This is called the Date badge."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"more_dates"}),": If the event has upcoming dates, the label ",e.jsx(t.code,{children:"+ more dates"})," is shown and it is linked to the full event page."]}),`
`]})]})}function q(n={}){const{wrapper:t}={...s(),...n.components};return t?e.jsx(t,{...n,children:e.jsx(i,{...n})}):i(n)}export{q as default};
