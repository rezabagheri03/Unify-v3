import{j as n}from"./jsx-runtime-DCCOt0jE.js";import{i as _}from"./DefaultPropsProvider-DqyRAa_x.js";import{r as y}from"./createSvgIcon-CmKh5iNO.js";import{d as h}from"./Star-DYWAAfsC.js";import{C as v,a as b}from"./CardContent-DrWxckEp.js";import{B as t}from"./Box-12JQxQAd.js";import{T as r}from"./Typography-B04KLrL4.js";import{C as j}from"./Chip-CLHrd8Bz.js";import{I as w}from"./IconButton-DrstuaXM.js";import"./index-BeMkoiPZ.js";import"./createSvgIcon-C52IgY8u.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./useControlled-BA1c7cCg.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./useId-BLJsOYdp.js";import"./useIsFocusVisible-B5BxoWYf.js";import"./Paper-DFqDosSp.js";import"./useTheme-CxRdof1M.js";import"./ButtonBase--MF_XyVn.js";var o={},C=_;Object.defineProperty(o,"__esModule",{value:!0});var f=o.default=void 0,D=C(y()),q=n;f=o.default=(0,D.default)((0,q.jsx)("path",{d:"M5 20h14v-2H5zM19 9h-4V3H9v6H5l7 7z"}),"Download");function x(e){const s=(e.mime||"").includes("pdf")||!e.mime;return n.jsx(v,{sx:{mb:1},children:n.jsxs(b,{sx:{display:"flex",alignItems:"center",justifyContent:"space-between"},children:[n.jsxs(t,{sx:{display:"flex",alignItems:"center",gap:1.5},children:[n.jsx(t,{sx:{width:44,height:56,borderRadius:1,display:"flex",alignItems:"center",justifyContent:"center",color:"white",fontWeight:700,fontSize:12,bgcolor:s?"#e53935":"#1976D2"},children:s?"PDF":"DOC"}),n.jsxs(t,{children:[n.jsx(r,{variant:"subtitle1",children:e.title}),n.jsxs(r,{variant:"body2",color:"text.secondary",children:[e.author,e.version?` • نسخه ${e.version}`:""]}),n.jsxs(t,{sx:{display:"flex",alignItems:"center",gap:2,mt:.5},children:[n.jsxs(r,{variant:"caption",sx:{display:"flex",alignItems:"center"},children:[n.jsx(h,{sx:{fontSize:16,color:"#f5a623"}})," ",e.average_rating??"—"," (",e.rating_count??0,")"]}),n.jsxs(r,{variant:"caption",children:["دانلود: ",e.download_count??0]}),e.badge_type&&n.jsx(j,{size:"small",label:e.badge_type,color:e.badge_type==="professor"?"secondary":"info"})]})]})]}),e.onDownload&&n.jsx(w,{color:"primary",onClick:()=>{var d;return(d=e.onDownload)==null?void 0:d.call(e,e.id)},"aria-label":"دانلود",children:n.jsx(f,{})})]})})}x.__docgenInfo={description:"P18 FileCard — PDF/DOCX icon, author, Shamsi date, rating, downloads, badge.",methods:[],displayName:"FileCard",props:{id:{required:!0,tsType:{name:"string"},description:""},title:{required:!1,tsType:{name:"string"},description:""},author:{required:!1,tsType:{name:"string"},description:""},average_rating:{required:!1,tsType:{name:"union",raw:"number | null",elements:[{name:"number"},{name:"null"}]},description:""},rating_count:{required:!1,tsType:{name:"number"},description:""},download_count:{required:!1,tsType:{name:"number"},description:""},badge_type:{required:!1,tsType:{name:"union",raw:"string | null",elements:[{name:"string"},{name:"null"}]},description:""},version:{required:!1,tsType:{name:"number"},description:""},mime:{required:!1,tsType:{name:"string"},description:""},onDownload:{required:!1,tsType:{name:"signature",type:"function",raw:"(id: string) => void",signature:{arguments:[{type:{name:"string"},name:"id"}],return:{name:"void"}}},description:""}}};const G={title:"Unify/FileCard",component:x},a={args:{id:"1",title:"جزوه فصل ۳",author:"دکتر رضایی",average_rating:4.2,rating_count:15,download_count:120,badge_type:"professor",mime:"application/pdf"}},i={args:{id:"2",title:"خلاصه نکات",author:"سارا احمدی",average_rating:null,rating_count:0,download_count:3,badge_type:null,mime:"application/vnd.openxmlformats-officedocument.wordprocessingml.document"}};var l,m,c;a.parameters={...a.parameters,docs:{...(l=a.parameters)==null?void 0:l.docs,source:{originalSource:`{
  args: {
    id: '1',
    title: 'جزوه فصل ۳',
    author: 'دکتر رضایی',
    average_rating: 4.2,
    rating_count: 15,
    download_count: 120,
    badge_type: 'professor',
    mime: 'application/pdf'
  }
}`,...(c=(m=a.parameters)==null?void 0:m.docs)==null?void 0:c.source}}};var u,p,g;i.parameters={...i.parameters,docs:{...(u=i.parameters)==null?void 0:u.docs,source:{originalSource:`{
  args: {
    id: '2',
    title: 'خلاصه نکات',
    author: 'سارا احمدی',
    average_rating: null,
    rating_count: 0,
    download_count: 3,
    badge_type: null,
    mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  }
}`,...(g=(p=i.parameters)==null?void 0:p.docs)==null?void 0:g.source}}};const J=["ProfessorPdf","StudentDocx"];export{a as ProfessorPdf,i as StudentDocx,J as __namedExportsOrder,G as default};
