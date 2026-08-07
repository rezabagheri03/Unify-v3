import{j as r}from"./jsx-runtime-DCCOt0jE.js";import{C as l,a as u}from"./CardContent-DrWxckEp.js";import{B as f}from"./Box-12JQxQAd.js";import{T as n}from"./Typography-B04KLrL4.js";import{C as y}from"./Chip-CLHrd8Bz.js";import"./index-BeMkoiPZ.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./Paper-DFqDosSp.js";import"./useTheme-CxRdof1M.js";import"./createSvgIcon-C52IgY8u.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./ButtonBase--MF_XyVn.js";import"./useIsFocusVisible-B5BxoWYf.js";const g={pending:"warning",syncing:"info",synced:"success",failed:"error",conflict:"error"};function m({item:e}){return r.jsx(l,{sx:{mb:1},children:r.jsxs(u,{sx:{display:"flex",justifyContent:"space-between",alignItems:"center"},children:[r.jsxs(f,{children:[r.jsx(n,{variant:"subtitle2",children:e.type}),r.jsxs(n,{variant:"caption",color:"text.secondary",children:[e.created_at," ",e.last_error?` • ${e.last_error}`:""]})]}),r.jsx(y,{size:"small",label:e.status,color:g[e.status]||"default"})]})})}m.__docgenInfo={description:"P18 OfflineQueueRow — offline sync queue item (F19).",methods:[],displayName:"OfflineQueueRow",props:{item:{required:!0,tsType:{name:"any"},description:""}}};const P={title:"Unify/OfflineQueueRow",component:m},t={args:{item:{id:1,type:"rating",status:"pending",created_at:"2024-09-21T10:00:00Z"}}},s={args:{item:{id:2,type:"ticket_reply",status:"failed",last_error:"network",created_at:"2024-09-21T10:00:00Z"}}};var a,o,i;t.parameters={...t.parameters,docs:{...(a=t.parameters)==null?void 0:a.docs,source:{originalSource:`{
  args: {
    item: {
      id: 1,
      type: 'rating',
      status: 'pending',
      created_at: '2024-09-21T10:00:00Z'
    }
  }
}`,...(i=(o=t.parameters)==null?void 0:o.docs)==null?void 0:i.source}}};var p,c,d;s.parameters={...s.parameters,docs:{...(p=s.parameters)==null?void 0:p.docs,source:{originalSource:`{
  args: {
    item: {
      id: 2,
      type: 'ticket_reply',
      status: 'failed',
      last_error: 'network',
      created_at: '2024-09-21T10:00:00Z'
    }
  }
}`,...(d=(c=s.parameters)==null?void 0:c.docs)==null?void 0:d.source}}};const q=["Pending","Failed"];export{s as Failed,t as Pending,q as __namedExportsOrder,P as default};
