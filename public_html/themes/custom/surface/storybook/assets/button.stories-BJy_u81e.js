import{p as _}from"./index-C-ZRuDWU.js";import{b as L}from"./button-Day_GlBX.js";const W={button:{icon:"",modifier:"",url:"",text:"Hi, I am a button"}},j={title:"Elements/Button"},t={render:I=>_(L(I)),args:{...W}},n={...t,name:"Primary button",args:{...t.args,button:{modifier:"button--primary",text:"Primary button"}}},r={...t,name:"Secondary button",args:{...t.args,button:{modifier:"button--secondary",text:"Secondary button",url:"https://medschool.ucla.edu"}}},o={...t,name:"Outlined button",args:{...t.args,button:{modifier:"button--outlined",text:"Outlined button"}}},a={...t,name:"Button with Icon",args:{...t.args,button:{icon:"fa-solid fa-arrow-right fa-sm",modifier:"button--primary",text:"Button with Icon"}}},e={...t,name:"Button as a link",args:{...t.args,button:{icon:"fa-solid fa-arrow-right fa-sm",modifier:"button--link",text:"Button as a link",url:"https://medschool.ucla.edu"}}};var s,u,i;t.parameters={...t.parameters,docs:{...(s=t.parameters)==null?void 0:s.docs,source:{originalSource:`{
  render: args => parse(button(args)),
  args: {
    ...data
  }
}`,...(i=(u=t.parameters)==null?void 0:u.docs)==null?void 0:i.source}}};var c,m,d,l,p;n.parameters={...n.parameters,docs:{...(c=n.parameters)==null?void 0:c.docs,source:{originalSource:`{
  ...Button,
  name: 'Primary button',
  args: {
    ...Button.args,
    button: {
      modifier: 'button--primary',
      text: 'Primary button'
    }
  }
}`,...(d=(m=n.parameters)==null?void 0:m.docs)==null?void 0:d.source},description:{story:"Primary button story",...(p=(l=n.parameters)==null?void 0:l.docs)==null?void 0:p.description}}};var b,g,f;r.parameters={...r.parameters,docs:{...(b=r.parameters)==null?void 0:b.docs,source:{originalSource:`{
  ...Button,
  name: 'Secondary button',
  args: {
    ...Button.args,
    button: {
      modifier: 'button--secondary',
      text: 'Secondary button',
      url: 'https://medschool.ucla.edu'
    }
  }
}`,...(f=(g=r.parameters)==null?void 0:g.docs)==null?void 0:f.source}}};var y,B,h;o.parameters={...o.parameters,docs:{...(y=o.parameters)==null?void 0:y.docs,source:{originalSource:`{
  ...Button,
  name: 'Outlined button',
  args: {
    ...Button.args,
    button: {
      modifier: 'button--outlined',
      text: 'Outlined button'
    }
  }
}`,...(h=(B=o.parameters)==null?void 0:B.docs)==null?void 0:h.source}}};var S,x,O;a.parameters={...a.parameters,docs:{...(S=a.parameters)==null?void 0:S.docs,source:{originalSource:`{
  ...Button,
  name: 'Button with Icon',
  args: {
    ...Button.args,
    button: {
      icon: 'fa-solid fa-arrow-right fa-sm',
      modifier: 'button--primary',
      text: 'Button with Icon'
    }
  }
}`,...(O=(x=a.parameters)==null?void 0:x.docs)==null?void 0:O.source}}};var P,k,w;e.parameters={...e.parameters,docs:{...(P=e.parameters)==null?void 0:P.docs,source:{originalSource:`{
  ...Button,
  name: 'Button as a link',
  args: {
    ...Button.args,
    button: {
      icon: 'fa-solid fa-arrow-right fa-sm',
      modifier: 'button--link',
      text: 'Button as a link',
      url: 'https://medschool.ucla.edu'
    }
  }
}`,...(w=(k=e.parameters)==null?void 0:k.docs)==null?void 0:w.source}}};const E=["Button","Primary","Secondary","Outlined","WithIcon","Link"],H=Object.freeze(Object.defineProperty({__proto__:null,Button:t,Link:e,Outlined:o,Primary:n,Secondary:r,WithIcon:a,__namedExportsOrder:E,default:j},Symbol.toStringTag,{value:"Module"}));export{H as B,e as L,o as O,n as P,r as S,a as W,t as a};
