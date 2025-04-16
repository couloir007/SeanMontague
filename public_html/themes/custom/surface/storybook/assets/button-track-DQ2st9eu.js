import{ae as e,af as r,ag as o}from"./index-B15VT6w7.js";import{useMDXComponents as i}from"./index-CcnH5Kt0.js";import{B as c,a,b as l}from"./button-track.stories-BCNU2aNF.js";import"./iframe-CGGnHX8c.js";import"../sb-preview/runtime.js";import"./index-RYns6xqu.js";import"./index-DAfSkmQi.js";import"./index-D-8MO0q_.js";import"./index-ar2LJKLv.js";import"./index-DrFu-skq.js";import"./index-C-ZRuDWU.js";import"./button-track-BskoRuOl.js";import"./twig-CEFeP58X.js";import"./twig-D5Zl0i6Y.js";function s(n){const t={code:"code",em:"em",h1:"h1",h2:"h2",li:"li",p:"p",pre:"pre",ul:"ul",...i(),...n.components};return e.jsxs(e.Fragment,{children:[e.jsx(r,{of:c}),`
`,e.jsx(t.h1,{id:"button-track",children:"Button track"}),`
`,e.jsx(t.p,{children:"The Button track component is used to track users actions or events when interacting with the buttons. This button is visually and structurally the same as the regular button but it contain extra properties.  See below."}),`
`,e.jsx(o,{of:a}),`
`,e.jsx(t.h2,{id:"button-with-icon",children:"Button with icon"}),`
`,e.jsx(o,{of:l}),`
`,e.jsx(t.h2,{id:"important",children:"IMPORTANT"}),`
`,e.jsxs(t.p,{children:[e.jsxs(t.em,{children:["As of May 2024, For some unknown reason, Storybook removes the ",e.jsx(t.code,{children:"onclick"})," attribute from the button. However, Drupal displays the ",e.jsx(t.code,{children:"onclick"})," attribute as expected. Currently we have not looked into this bug"]}),"."]}),`
`,e.jsx(t.h2,{id:"button-technical-specs",children:"Button technical specs"}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"modifier"})," makes it possible to pass a CSS modifier class which in turn can change the button's appearance as you see the examples below."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"text"})," is the label of the button."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"url"})," is also an optional property which determines if we use a ",e.jsx(t.code,{children:"<button>"})," or an ",e.jsx(t.code,{children:"<a>"})," element based on the presence of the ",e.jsx(t.code,{children:"url"}),"."]}),`
`]}),`
`,e.jsxs(t.p,{children:[e.jsx(t.em,{children:"Extra properties"}),":"]}),`
`,e.jsxs(t.ul,{children:[`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"action"})," tracks various actions such as download, play, etc."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"category"})," tracks things like video, media, etc."]}),`
`,e.jsxs(t.li,{children:[e.jsx(t.code,{children:"label"})," provides an optional label tot he tracking button but it's not visible."]}),`
`]}),`
`,e.jsx(t.p,{children:"In code all properties above are used as follows:"}),`
`,e.jsx(t.pre,{children:e.jsx(t.code,{className:"language-html",children:`<a href="{{ url }}" class="{{ modifier }}" onclick="_sz.push(['event', 'CATEGORY', 'ACTION', 'LABEL']);">{{ text }}</a>
`})})]})}function B(n={}){const{wrapper:t}={...i(),...n.components};return t?e.jsx(t,{...n,children:e.jsx(s,{...n})}):s(n)}export{B as default};
