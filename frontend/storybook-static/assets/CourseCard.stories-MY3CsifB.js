import{j as r}from"./jsx-runtime-DCCOt0jE.js";import{C as g,a as v}from"./CardContent-DrWxckEp.js";import{B as _}from"./Box-12JQxQAd.js";import{T as o}from"./Typography-B04KLrL4.js";import{C as h}from"./Chip-CLHrd8Bz.js";import"./index-BeMkoiPZ.js";import"./DefaultPropsProvider-DqyRAa_x.js";import"./generateUtilityClasses-BIQBxJxf.js";import"./Paper-DFqDosSp.js";import"./useTheme-CxRdof1M.js";import"./createSvgIcon-C52IgY8u.js";import"./TransitionGroupContext-C-4pJNxd.js";import"./useEnhancedEffect-DjT7YI6T.js";import"./ButtonBase--MF_XyVn.js";import"./useIsFocusVisible-B5BxoWYf.js";function j({spec:e,footerAction:i}){var d,m,c,l,p,u,f;const b=`hsl(${(s=>{if(!s)return 200;let a=0;for(let n=0;n<s.length;n++)a=(a*31+s.charCodeAt(n))%360;return a})(((d=e.professor)==null?void 0:d.first_name)||((m=e.course)==null?void 0:m.code))}, 55%, 42%)`;return r.jsx(g,{sx:{mb:1,borderTop:`4px solid ${b}`},children:r.jsxs(v,{children:[r.jsxs(_,{sx:{display:"flex",justifyContent:"space-between",alignItems:"center"},children:[r.jsxs(o,{variant:"subtitle1",children:[(c=e.course)==null?void 0:c.name,((l=e.course)==null?void 0:l.code)&&r.jsx(h,{size:"small",label:e.course.code,sx:{ml:1}})]}),r.jsx(h,{size:"small",label:`${((p=e.course)==null?void 0:p.credits)??"—"} واحد`,variant:"outlined"})]}),r.jsxs(o,{variant:"body2",color:"text.secondary",children:[(u=e.professor)==null?void 0:u.first_name," ",(f=e.professor)==null?void 0:f.last_name]}),r.jsxs(o,{variant:"body2",children:[e.day_of_week," ",e.time_start,"–",e.time_end,e.is_next_day?" (روز بعد)":""," • ",e.location]}),e.shamsi_final&&r.jsxs(o,{variant:"caption",color:"text.secondary",children:["امتحان: ",e.shamsi_final]}),i&&r.jsx(_,{sx:{mt:1},children:i})]})})}j.__docgenInfo={description:`P18 CourseCard — deterministic hash-colored header, day+time, location,
credits and exam date. Optionally links to the course's resources.`,methods:[],displayName:"CourseCard",props:{spec:{required:!0,tsType:{name:"CourseCardSpec"},description:""},footerAction:{required:!1,tsType:{name:"ReactReactNode",raw:"React.ReactNode"},description:""}}};const U={title:"Unify/CourseCard",component:j},t={args:{spec:{id:"1",course:{name:"ریاضی ۲",code:"CS102",credits:3},professor:{first_name:"دکتر",last_name:"رضایی"},day_of_week:"شنبه",time_start:"08:00",time_end:"10:00",location:"کلاس ۱۰۱",shamsi_final:"1403/04/22"}}};var x,C,y;t.parameters={...t.parameters,docs:{...(x=t.parameters)==null?void 0:x.docs,source:{originalSource:`{
  args: {
    spec: {
      id: '1',
      course: {
        name: 'ریاضی ۲',
        code: 'CS102',
        credits: 3
      },
      professor: {
        first_name: 'دکتر',
        last_name: 'رضایی'
      },
      day_of_week: 'شنبه',
      time_start: '08:00',
      time_end: '10:00',
      location: 'کلاس ۱۰۱',
      shamsi_final: '1403/04/22'
    }
  }
}`,...(y=(C=t.parameters)==null?void 0:C.docs)==null?void 0:y.source}}};const F=["Default"];export{t as Default,F as __namedExportsOrder,U as default};
