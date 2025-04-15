import{p as n}from"./index-C-ZRuDWU.js";import{T as v,t as x}from"./twig-CEFeP58X.js";import{a as P,D as H}from"./twig-D5Zl0i6Y.js";P(v);v.cache(!1);const c=t=>t,o=(t={})=>{const i=x.twig({id:"/media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/elements/tables/tables.twig",data:[{type:"raw",value:'<table class="tablefield container',position:{start:0,end:34}},{type:"output",position:{start:34,end:65},stack:[{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:34,end:65}},{type:"Twig.expression.type.string",value:" ",position:{start:34,end:65}},{type:"Twig.expression.type.variable",value:"modifier",match:["modifier"],position:{start:34,end:65}},{type:"Twig.expression.type.operator.binary",value:"~",position:{start:34,end:65},precidence:6,associativity:"leftToRight",operator:"~"},{type:"Twig.expression.type.operator.binary",value:"?",position:{start:34,end:65},precidence:16,associativity:"rightToLeft",operator:"?"}]},{type:"raw",value:`">
	<caption>`,position:{start:65,end:78}},{type:"output",position:{start:78,end:91},stack:[{type:"Twig.expression.type.variable",value:"caption",match:["caption"],position:{start:78,end:91}}]},{type:"raw",value:`</caption>
	<thead>
		<tr>
			<th class="row_0 col_0">Header item</th>
			<th class="row_0 col_1">Header item</th>
			<th class="row_0 col_2">Header item</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row_1 col_0">
				<strong class="tablesaw-cell-label" aria-hidden="true">Header item</strong>
				<span class="tablesaw-cell-content">Table item</span>
			</td>
			<td class="row_1 col_1">
				<strong class="tablesaw-cell-label" aria-hidden="true">Header item</strong>
				<span class="tablesaw-cell-content">Table item</span>
			</td>
			<td class="row_1 col_2">
				<strong class="tablesaw-cell-label" aria-hidden="true">Header item</strong>
				<span class="tablesaw-cell-content">Table item</span>
			</td>
		</tr>
		<tr>
			<td class="row_2 col_0">
				<strong class="tablesaw-cell-label" aria-hidden="true">Header item</strong>
				<span class="tablesaw-cell-content">Table item</span>
			</td>
			<td class="row_2 col_1">
				<strong class="tablesaw-cell-label" aria-hidden="true">Header item</strong>
				<span class="tablesaw-cell-content">Table item</span>
			</td>
			<td class="row_2 col_2">
				<strong class="tablesaw-cell-label" aria-hidden="true">Header item</strong>
				<span class="tablesaw-cell-content">Table item</span>
			</td>
		</tr>
	</tbody>
</table>
`,position:{start:91,end:91}}],precompiled:!0});i.options.allowInlineIncludes=!0;try{return c(i.render({attributes:new H,...t}))}catch(S){return c("An error occurred whilst rendering /media/sean/External/www/OceanPortalPantheon/web/themes/custom/surface/source/patterns/elements/tables/tables.twig: "+S.toString())}},l={modifier:"base-table"},B={title:"Elements/Tables"},e={name:"Base table",render:t=>n(o(t)),args:{...l}},a={name:"Primary table",render:t=>n(o(t)),args:{...l,modifier:"table--primary"}},r={name:"Stripped table",render:t=>n(o(t)),args:{...l,modifier:"table--primary table--striped"}},s={name:"Table without border",render:t=>n(o(t)),args:{...l,modifier:"table--hover"}};var d,p,m;e.parameters={...e.parameters,docs:{...(d=e.parameters)==null?void 0:d.docs,source:{originalSource:`{
  name: 'Base table',
  render: args => parse(tables(args)),
  args: {
    ...data
  }
}`,...(m=(p=e.parameters)==null?void 0:p.docs)==null?void 0:m.source}}};var b,u,g;a.parameters={...a.parameters,docs:{...(b=a.parameters)==null?void 0:b.docs,source:{originalSource:`{
  name: 'Primary table',
  render: args => parse(tables(args)),
  args: {
    ...data,
    modifier: 'table--primary'
  }
}`,...(g=(u=a.parameters)==null?void 0:u.docs)==null?void 0:g.source}}};var w,y,h;r.parameters={...r.parameters,docs:{...(w=r.parameters)==null?void 0:w.docs,source:{originalSource:`{
  name: 'Stripped table',
  render: args => parse(tables(args)),
  args: {
    ...data,
    modifier: 'table--primary table--striped'
  }
}`,...(h=(y=r.parameters)==null?void 0:y.docs)==null?void 0:h.source}}};var T,f,_;s.parameters={...s.parameters,docs:{...(T=s.parameters)==null?void 0:T.docs,source:{originalSource:`{
  name: 'Table without border',
  render: args => parse(tables(args)),
  args: {
    ...data,
    modifier: 'table--hover'
  }
}`,...(_=(f=s.parameters)==null?void 0:f.docs)==null?void 0:_.source}}};const E=["Base","PrimaryTable","StrippedTable","NoBorder"],N=Object.freeze(Object.defineProperty({__proto__:null,Base:e,NoBorder:s,PrimaryTable:a,StrippedTable:r,__namedExportsOrder:E,default:B},Symbol.toStringTag,{value:"Module"}));export{e as B,s as N,a as P,r as S,N as T};
