import{j as s}from"./jsx-runtime-DCCOt0jE.js";import{C as u,a as x}from"./CardContent-DrWxckEp.js";import{B as g}from"./Box-12JQxQAd.js";import{T as r}from"./Typography-B04KLrL4.js";import{C as i}from"./Chip-CLHrd8Bz.js";import"./index-BeMkoiPZ.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./Paper-DFqDosSp.js";import"./useTheme-CxRdof1M.js";import"./createSvgIcon-C52IgY8u.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./ButtonBase--MF_XyVn.js";import"./useIsFocusVisible-B5BxoWYf.js";function l({message:c,onClick:a}){var n,o;const e=c||{};return s.jsx(u,{sx:{mb:1,cursor:a?"pointer":"default"},onClick:a,children:s.jsxs(x,{children:[s.jsxs(g,{sx:{display:"flex",justifyContent:"space-between"},children:[s.jsxs(r,{variant:"subtitle1",children:[e.subject||"(بدون موضوع)",e.is_edited&&s.jsx(i,{size:"small",label:"ویرایش شده",sx:{ml:1}}),e.is_deleted&&s.jsx(i,{size:"small",label:"حذف شده",color:"error",sx:{ml:1}})]}),s.jsx(i,{size:"small",label:e.priority||"normal",variant:"outlined"})]}),s.jsxs(r,{variant:"body2",color:"text.secondary",children:[(n=e.sender)==null?void 0:n.first_name," ",(o=e.sender)==null?void 0:o.last_name,e.sent_at?` • ${new Date(e.sent_at).toLocaleString("fa-IR")}`:""]}),s.jsx(r,{variant:"body2",sx:{mt:.5},children:e.body})]})})}l.__docgenInfo={description:"P18 MessageRow — single inbox row with read/edited/deleted states.",methods:[],displayName:"MessageRow",props:{message:{required:!0,tsType:{name:"any"},description:""},onClick:{required:!1,tsType:{name:"signature",type:"function",raw:"() => void",signature:{arguments:[],return:{name:"void"}}},description:""}}};const B={title:"Unify/MessageRow",component:l},t={args:{message:{id:"1",subject:"تغییر برنامه",body:"کلاس لغو شد",sender:{first_name:"دکتر",last_name:"رضایی"},is_edited:!0,sent_at:"2024-09-21T10:00:00Z",priority:"high"}}};var d,m,p;t.parameters={...t.parameters,docs:{...(d=t.parameters)==null?void 0:d.docs,source:{originalSource:`{
  args: {
    message: {
      id: '1',
      subject: 'تغییر برنامه',
      body: 'کلاس لغو شد',
      sender: {
        first_name: 'دکتر',
        last_name: 'رضایی'
      },
      is_edited: true,
      sent_at: '2024-09-21T10:00:00Z',
      priority: 'high'
    }
  }
}`,...(p=(m=t.parameters)==null?void 0:m.docs)==null?void 0:p.source}}};const I=["Edited"];export{t as Edited,I as __namedExportsOrder,B as default};
