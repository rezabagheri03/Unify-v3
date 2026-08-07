import{j as e}from"./jsx-runtime-DCCOt0jE.js";import{T as r}from"./Typography-B04KLrL4.js";import{B as c}from"./Box-12JQxQAd.js";import{C as l,a as p}from"./CardContent-DrWxckEp.js";import"./index-BeMkoiPZ.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./useTheme-CxRdof1M.js";import"./Paper-DFqDosSp.js";function d({events:o}){return o!=null&&o.length?e.jsx(c,{sx:{display:"flex",gap:1.5,overflowX:"auto",pb:1},children:o.map(t=>e.jsx(l,{sx:{minWidth:220,borderTop:`4px solid ${t.color_code||"#1976D2"}`},children:e.jsxs(p,{children:[e.jsx(r,{variant:"subtitle2",children:t.title}),e.jsxs(r,{variant:"caption",color:"text.secondary",sx:{display:"block",mb:.5},children:[t.start_date_g?new Date(t.start_date_g).toLocaleDateString("fa-IR"):"",t.end_date_g&&t.end_date_g!==t.start_date_g?` — ${new Date(t.end_date_g).toLocaleDateString("fa-IR")}`:""]}),e.jsx(r,{variant:"body2",children:t.description}),e.jsx(r,{variant:"caption",color:"text.secondary",children:t.event_type})]})},t.id))}):e.jsx(r,{color:"text.secondary",children:"رویدادی ثبت نشده"})}d.__docgenInfo={description:"P18 Timeline — horizontal academic calendar cards (F11).",methods:[],displayName:"Timeline",props:{events:{required:!0,tsType:{name:"Array",elements:[{name:"CalendarEvent"}],raw:"CalendarEvent[]"},description:""}}};const v={title:"Unify/Timeline",component:d},n={args:{events:[{id:"1",title:"شروع ثبت‌نام",description:"آغاز ثبت‌نام",start_date_g:"2024-09-20",event_type:"registration_open",color_code:"#4CAF50"},{id:"2",title:"پایان ثبت‌نام",description:"مهلت",start_date_g:"2024-09-28",event_type:"registration_close",color_code:"#F44336"}]}};var a,i,s;n.parameters={...n.parameters,docs:{...(a=n.parameters)==null?void 0:a.docs,source:{originalSource:`{
  args: {
    events: [{
      id: '1',
      title: 'شروع ثبت‌نام',
      description: 'آغاز ثبت‌نام',
      start_date_g: '2024-09-20',
      event_type: 'registration_open',
      color_code: '#4CAF50'
    }, {
      id: '2',
      title: 'پایان ثبت‌نام',
      description: 'مهلت',
      start_date_g: '2024-09-28',
      event_type: 'registration_close',
      color_code: '#F44336'
    }]
  }
}`,...(s=(i=n.parameters)==null?void 0:i.docs)==null?void 0:s.source}}};const C=["Default"];export{n as Default,C as __namedExportsOrder,v as default};
