import{j as t}from"./jsx-runtime-DCCOt0jE.js";import{S as u}from"./StatusBadge-B9XeURJn.js";import{G as x}from"./GradeChip-zkwo2Kht.js";import{C as _,a as f}from"./CardContent-DrWxckEp.js";import{B as r}from"./Box-12JQxQAd.js";import{T as n}from"./Typography-B04KLrL4.js";import"./index-BeMkoiPZ.js";import"./Chip-CLHrd8Bz.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./createSvgIcon-C52IgY8u.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./ButtonBase--MF_XyVn.js";import"./useIsFocusVisible-B5BxoWYf.js";import"./Paper-DFqDosSp.js";import"./useTheme-CxRdof1M.js";function c({assignment:l}){const s=l||{};return t.jsx(_,{sx:{mb:1},children:t.jsxs(f,{children:[t.jsxs(r,{sx:{display:"flex",justifyContent:"space-between"},children:[t.jsx(n,{variant:"subtitle1",children:s.title}),t.jsxs(r,{sx:{display:"flex",gap:1},children:[t.jsx(u,{status:s.status}),t.jsx(x,{grade:s.grade})]})]}),t.jsxs(n,{variant:"body2",color:"text.secondary",children:["مهلت: ",s.shamsi_original||(s.due_date_g?new Date(s.due_date_g).toLocaleDateString("fa-IR"):"—")]})]})})}c.__docgenInfo={description:"P18 AssignmentCard — tracker row with status + grade.",methods:[],displayName:"AssignmentCard",props:{assignment:{required:!0,tsType:{name:"any"},description:""}}};const k={title:"Unify/AssignmentCard",component:c},e={args:{assignment:{id:"1",title:"تمرین ۱",status:"graded",grade:18.5,shamsi_original:"1403/08/15"}}},a={args:{assignment:{id:"2",title:"پروژه",status:"late",due_date_g:"2024-10-01"}}};var i,o,d;e.parameters={...e.parameters,docs:{...(i=e.parameters)==null?void 0:i.docs,source:{originalSource:`{
  args: {
    assignment: {
      id: '1',
      title: 'تمرین ۱',
      status: 'graded',
      grade: 18.5,
      shamsi_original: '1403/08/15'
    }
  }
}`,...(d=(o=e.parameters)==null?void 0:o.docs)==null?void 0:d.source}}};var m,p,g;a.parameters={...a.parameters,docs:{...(m=a.parameters)==null?void 0:m.docs,source:{originalSource:`{
  args: {
    assignment: {
      id: '2',
      title: 'پروژه',
      status: 'late',
      due_date_g: '2024-10-01'
    }
  }
}`,...(g=(p=a.parameters)==null?void 0:p.docs)==null?void 0:g.source}}};const q=["Graded","Late"];export{e as Graded,a as Late,q as __namedExportsOrder,k as default};
