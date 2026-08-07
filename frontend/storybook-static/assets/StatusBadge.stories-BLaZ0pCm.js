import{j as t}from"./jsx-runtime-DCCOt0jE.js";import{S as a}from"./StatusBadge-B9XeURJn.js";import"./index-BeMkoiPZ.js";import"./Chip-CLHrd8Bz.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./createSvgIcon-C52IgY8u.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./ButtonBase--MF_XyVn.js";import"./useIsFocusVisible-B5BxoWYf.js";const B={title:"Unify/StatusBadge",component:a},s={render:()=>t.jsx("div",{style:{display:"flex",gap:8},children:["open","in_progress","answered","closed"].map(e=>t.jsx(a,{status:e},e))})},r={render:()=>t.jsx("div",{style:{display:"flex",gap:8},children:["pending","approved","rejected"].map(e=>t.jsx(a,{status:e},e))})};var o,p,d;s.parameters={...s.parameters,docs:{...(o=s.parameters)==null?void 0:o.docs,source:{originalSource:`{
  render: () => <div style={{
    display: 'flex',
    gap: 8
  }}>{['open', 'in_progress', 'answered', 'closed'].map(s => <StatusBadge key={s} status={s} />)}</div>
}`,...(d=(p=s.parameters)==null?void 0:p.docs)==null?void 0:d.source}}};var n,i,m;r.parameters={...r.parameters,docs:{...(n=r.parameters)==null?void 0:n.docs,source:{originalSource:`{
  render: () => <div style={{
    display: 'flex',
    gap: 8
  }}>{['pending', 'approved', 'rejected'].map(s => <StatusBadge key={s} status={s} />)}</div>
}`,...(m=(i=r.parameters)==null?void 0:i.docs)==null?void 0:m.source}}};const _=["TicketStates","ResourceStates"];export{r as ResourceStates,s as TicketStates,_ as __namedExportsOrder,B as default};
