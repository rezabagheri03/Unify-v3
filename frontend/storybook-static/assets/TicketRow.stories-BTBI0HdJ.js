import{j as e}from"./jsx-runtime-DCCOt0jE.js";import{S as d}from"./StatusBadge-B9XeURJn.js";import{C as m,a as u}from"./CardContent-DrWxckEp.js";import{B as l}from"./Box-12JQxQAd.js";import{T as s}from"./Typography-B04KLrL4.js";import"./index-BeMkoiPZ.js";import"./Chip-CLHrd8Bz.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./createSvgIcon-C52IgY8u.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./ButtonBase--MF_XyVn.js";import"./useIsFocusVisible-B5BxoWYf.js";import"./Paper-DFqDosSp.js";import"./useTheme-CxRdof1M.js";function p({ticket:c,onClick:o}){const t=c||{};return e.jsx(m,{sx:{mb:1,cursor:o?"pointer":"default"},onClick:o,children:e.jsxs(u,{children:[e.jsxs(l,{sx:{display:"flex",justifyContent:"space-between"},children:[e.jsx(s,{variant:"subtitle1",children:t.subject}),e.jsx(d,{status:t.status})]}),e.jsxs(s,{variant:"body2",color:"text.secondary",children:[t.department,t.created_at?` • ${new Date(t.created_at).toLocaleString("fa-IR")}`:"",t.is_escalated?" • ارجاع شده":""]}),e.jsx(s,{variant:"body2",sx:{mt:.5},children:t.description})]})})}p.__docgenInfo={description:"P18 TicketRow — support ticket list row.",methods:[],displayName:"TicketRow",props:{ticket:{required:!0,tsType:{name:"any"},description:""},onClick:{required:!1,tsType:{name:"signature",type:"function",raw:"() => void",signature:{arguments:[],return:{name:"void"}}},description:""}}};const O={title:"Unify/TicketRow",component:p},r={args:{ticket:{id:"1",subject:"مشکل ثبت‌نام",description:"نمیتوانم واحد اضافه کنم",department:"education",status:"open",created_at:"2024-09-21T10:00:00Z"}}};var a,i,n;r.parameters={...r.parameters,docs:{...(a=r.parameters)==null?void 0:a.docs,source:{originalSource:`{
  args: {
    ticket: {
      id: '1',
      subject: 'مشکل ثبت‌نام',
      description: 'نمیتوانم واحد اضافه کنم',
      department: 'education',
      status: 'open',
      created_at: '2024-09-21T10:00:00Z'
    }
  }
}`,...(n=(i=r.parameters)==null?void 0:i.docs)==null?void 0:n.source}}};const q=["Open"];export{r as Open,q as __namedExportsOrder,O as default};
