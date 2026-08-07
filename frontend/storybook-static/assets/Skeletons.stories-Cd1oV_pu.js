import{j as n}from"./jsx-runtime-DCCOt0jE.js";import{C as R,a as F}from"./CardContent-DrWxckEp.js";import{k as g,l as X,u as P,_ as V,a as p,m as x,n as A}from"./DefaultPropsProvider-DqyRAa_x.js";import{r as W}from"./index-BeMkoiPZ.js";import{g as q,a as B,c as K,b as D,s as L}from"./generateUtilityClasses-BIQBxJxf.js";import"./Paper-DFqDosSp.js";function z(e,t=0,a=1){return X(e,t,a)}function G(e){e=e.slice(1);const t=new RegExp(`.{1,${e.length>=6?2:1}}`,"g");let a=e.match(t);return a&&a[0].length===1&&(a=a.map(r=>r+r)),a?`rgb${a.length===4?"a":""}(${a.map((r,s)=>s<3?parseInt(r,16):Math.round(parseInt(r,16)/255*1e3)/1e3).join(", ")})`:""}function M(e){if(e.type)return e;if(e.charAt(0)==="#")return M(G(e));const t=e.indexOf("("),a=e.substring(0,t);if(["rgb","rgba","hsl","hsla","color"].indexOf(a)===-1)throw new Error(g(9,e));let r=e.substring(t+1,e.length-1),s;if(a==="color"){if(r=r.split(" "),s=r.shift(),r.length===4&&r[3].charAt(0)==="/"&&(r[3]=r[3].slice(1)),["srgb","display-p3","a98-rgb","prophoto-rgb","rec-2020"].indexOf(s)===-1)throw new Error(g(10,s))}else r=r.split(",");return r=r.map(i=>parseFloat(i)),{type:a,values:r,colorSpace:s}}function H(e){const{type:t,colorSpace:a}=e;let{values:r}=e;return t.indexOf("rgb")!==-1?r=r.map((s,i)=>i<3?parseInt(s,10):s):t.indexOf("hsl")!==-1&&(r[1]=`${r[1]}%`,r[2]=`${r[2]}%`),t.indexOf("color")!==-1?r=`${a} ${r.join(" ")}`:r=`${r.join(", ")}`,`${t}(${r})`}function J(e,t){return e=M(e),t=z(t),(e.type==="rgb"||e.type==="hsl")&&(e.type+="a"),e.type==="color"?e.values[3]=`/${t}`:e.values[3]=t,H(e)}function Q(e){return String(e).match(/[\d.\-+]*\s*(.*)/)[1]||""}function Y(e){return parseFloat(e)}function Z(e){return q("MuiSkeleton",e)}B("MuiSkeleton",["root","text","rectangular","rounded","circular","pulse","wave","withChildren","fitContent","heightAuto"]);const ee=["animation","className","component","height","style","variant","width"];let c=e=>e,C,b,y,v;const te=e=>{const{classes:t,variant:a,animation:r,hasChildren:s,width:i,height:l}=e;return D({root:["root",a,r,s&&"withChildren",s&&!i&&"fitContent",s&&!l&&"heightAuto"]},Z,t)},re=A(C||(C=c`
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0.4;
  }

  100% {
    opacity: 1;
  }
`)),ae=A(b||(b=c`
  0% {
    transform: translateX(-100%);
  }

  50% {
    /* +0.5s of delay between each loop */
    transform: translateX(100%);
  }

  100% {
    transform: translateX(100%);
  }
`)),ne=L("span",{name:"MuiSkeleton",slot:"Root",overridesResolver:(e,t)=>{const{ownerState:a}=e;return[t.root,t[a.variant],a.animation!==!1&&t[a.animation],a.hasChildren&&t.withChildren,a.hasChildren&&!a.width&&t.fitContent,a.hasChildren&&!a.height&&t.heightAuto]}})(({theme:e,ownerState:t})=>{const a=Q(e.shape.borderRadius)||"px",r=Y(e.shape.borderRadius);return p({display:"block",backgroundColor:e.vars?e.vars.palette.Skeleton.bg:J(e.palette.text.primary,e.palette.mode==="light"?.11:.13),height:"1.2em"},t.variant==="text"&&{marginTop:0,marginBottom:0,height:"auto",transformOrigin:"0 55%",transform:"scale(1, 0.60)",borderRadius:`${r}${a}/${Math.round(r/.6*10)/10}${a}`,"&:empty:before":{content:'"\\00a0"'}},t.variant==="circular"&&{borderRadius:"50%"},t.variant==="rounded"&&{borderRadius:(e.vars||e).shape.borderRadius},t.hasChildren&&{"& > *":{visibility:"hidden"}},t.hasChildren&&!t.width&&{maxWidth:"fit-content"},t.hasChildren&&!t.height&&{height:"auto"})},({ownerState:e})=>e.animation==="pulse"&&x(y||(y=c`
      animation: ${0} 2s ease-in-out 0.5s infinite;
    `),re),({ownerState:e,theme:t})=>e.animation==="wave"&&x(v||(v=c`
      position: relative;
      overflow: hidden;

      /* Fix bug in Safari https://bugs.webkit.org/show_bug.cgi?id=68196 */
      -webkit-mask-image: -webkit-radial-gradient(white, black);

      &::after {
        animation: ${0} 2s linear 0.5s infinite;
        background: linear-gradient(
          90deg,
          transparent,
          ${0},
          transparent
        );
        content: '';
        position: absolute;
        transform: translateX(-100%); /* Avoid flash during server-side hydration */
        bottom: 0;
        left: 0;
        right: 0;
        top: 0;
      }
    `),ae,(t.vars||t).palette.action.hover)),o=W.forwardRef(function(t,a){const r=P({props:t,name:"MuiSkeleton"}),{animation:s="pulse",className:i,component:l="span",height:h,style:E,variant:I="text",width:N}=r,m=V(r,ee),f=p({},r,{animation:s,component:l,variant:I,hasChildren:!!m.children}),T=te(f);return n.jsx(ne,p({as:l,ref:a,className:K(T.root,i),ownerState:f},m,{style:p({width:N,height:h},E)}))});function O({count:e=3}){return n.jsx(n.Fragment,{children:Array.from({length:e}).map((t,a)=>n.jsx(R,{sx:{mb:1},children:n.jsxs(F,{children:[n.jsx(o,{variant:"text",width:"50%"}),n.jsx(o,{variant:"text",width:"35%"}),n.jsx(o,{variant:"text",width:"70%"})]})},a))})}function U({count:e=3}){return n.jsx(n.Fragment,{children:Array.from({length:e}).map((t,a)=>n.jsx(R,{sx:{mb:1},children:n.jsxs(F,{sx:{display:"flex",gap:2},children:[n.jsx(o,{variant:"rectangular",width:44,height:56}),n.jsx(o,{variant:"text",width:"60%"})]})},a))})}O.__docgenInfo={description:"P18 SkeletonCards — loading placeholders matching CourseCard / FileCard.",methods:[],displayName:"CourseCardSkeleton",props:{count:{required:!1,tsType:{name:"number"},description:"",defaultValue:{value:"3",computed:!1}}}};U.__docgenInfo={description:"",methods:[],displayName:"FileCardSkeleton",props:{count:{required:!1,tsType:{name:"number"},description:"",defaultValue:{value:"3",computed:!1}}}};const pe={title:"Unify/Skeletons"},d={render:()=>n.jsx(O,{count:3})},u={render:()=>n.jsx(U,{count:3})};var k,w,j;d.parameters={...d.parameters,docs:{...(k=d.parameters)==null?void 0:k.docs,source:{originalSource:`{
  render: () => <CourseCardSkeleton count={3} />
}`,...(j=(w=d.parameters)==null?void 0:w.docs)==null?void 0:j.source}}};var S,_,$;u.parameters={...u.parameters,docs:{...(S=u.parameters)==null?void 0:S.docs,source:{originalSource:`{
  render: () => <FileCardSkeleton count={3} />
}`,...($=(_=u.parameters)==null?void 0:_.docs)==null?void 0:$.source}}};const ce=["Courses","Files"];export{d as Courses,u as Files,ce as __namedExportsOrder,pe as default};
