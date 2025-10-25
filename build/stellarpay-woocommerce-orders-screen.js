(()=>{"use strict";var t={1020:(t,e,n)=>{var o=n(1609),r=Symbol.for("react.element"),i=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),a=o.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,s={key:!0,ref:!0,__self:!0,__source:!0};function c(t,e,n){var o,c={},l=null,p=null;for(o in void 0!==n&&(l=""+n),void 0!==e.key&&(l=""+e.key),void 0!==e.ref&&(p=e.ref),e)i.call(e,o)&&!s.hasOwnProperty(o)&&(c[o]=e[o]);if(t&&t.defaultProps)for(o in e=t.defaultProps)void 0===c[o]&&(c[o]=e[o]);return{$$typeof:r,type:t,key:l,ref:p,props:c,_owner:a.current}}e.jsx=c,e.jsxs=c},1609:t=>{t.exports=window.React},4848:(t,e,n)=>{t.exports=n(1020)}},e={};function n(o){var r=e[o];if(void 0!==r)return r.exports;var i=e[o]={exports:{}};return t[o](i,i.exports,n),i.exports}n.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return n.d(e,{a:e}),e},n.d=(t,e)=>{for(var o in e)n.o(e,o)&&!n.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:e[o]})},n.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e);var o=n(4848);const r=window.wp.domReady;var i=n.n(r);const a=window.wp.element;var s=n(1609),c=n.n(s);function l(t,e){(null==e||e>t.length)&&(e=t.length);for(var n=0,o=Array(e);n<e;n++)o[n]=t[n];return o}var p=s.createContext(null);p.displayName="ConnectComponents";var d,u=function(t){var e=t.connectInstance,n=t.children;return s.createElement(p.Provider,{value:{connectInstance:e}},n)},f=function(t){var e=function(t,e){return function(t){if(Array.isArray(t))return t}(t)||function(t,e){var n=null==t?null:"undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(null!=n){var o,r,i,a,s=[],c=!0,l=!1;try{if(i=(n=n.call(t)).next,0===e){if(Object(n)!==n)return;c=!1}else for(;!(c=(o=i.call(n)).done)&&(s.push(o.value),s.length!==e);c=!0);}catch(t){l=!0,r=t}finally{try{if(!c&&null!=n.return&&(a=n.return(),Object(a)!==a))return}finally{if(l)throw r}}return s}}(t,e)||function(t,e){if(t){if("string"==typeof t)return l(t,e);var n={}.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?l(t,e):void 0}}(t,e)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}(s.useState(null),2),n=e[0],o=e[1],r=function(){var t=s.useContext(p);if(!t)throw new Error("Could not find Components context; You need to wrap the part of your app in an <ConnectComponentsProvider> provider.");return t}().connectInstance,i=s.useRef(null),a=s.createElement("div",{style:{width:"100%"},ref:i});return s.useLayoutEffect((function(){if(null!==i.current&&null===n){var e=r.create(t);if(o(e),null!==e){try{e.setAttribute("reactSdkAnalytics","3.3.25-preview-1")}catch(t){console.log("Error setting React Sdk version with error message: ",t)}for(;i.current.firstChild;)i.current.removeChild(i.current.firstChild);i.current.appendChild(e)}}}),[r,t]),{wrapper:a,component:n}},m=function(t,e,n){c().useEffect((function(){if(t)try{n(t,e)}catch(t){return void console.error("Error when calling setter! ",t)}}),[t,e,n])},y=function(t){var e=t.payment,n=t.onClose,o=t.onLoadError,r=t.onLoaderStart,i=f("payment-details"),a=i.wrapper,s=i.component;return m(s,e,(function(t,e){return t.setPayment(e)})),m(s,n,(function(t,e){return t.setOnClose(e)})),m(s,r,(function(t,e){t.setOnLoaderStart(e)})),m(s,o,(function(t,e){t.setOnLoadError(e)})),a};!function(t){t.exit="exit",t.close="close",t.instantPayoutCreated="instantpayoutcreated"}(d||(d={}));let g={data:""},h=t=>"object"==typeof window?((t?t.querySelector("#_goober"):window._goober)||Object.assign((t||document.head).appendChild(document.createElement("style")),{innerHTML:" ",id:"_goober"})).firstChild:t||g,w=/(?:([\u0080-\uFFFF\w-%@]+) *:? *([^{;]+?);|([^;}{]*?) *{)|(}\s*)/g,b=/\/\*[^]*?\*\/|  +/g,v=/\n+/g,x=(t,e)=>{let n="",o="",r="";for(let i in t){let a=t[i];"@"==i[0]?"i"==i[1]?n=i+" "+a+";":o+="f"==i[1]?x(a,i):i+"{"+x(a,"k"==i[1]?"":e)+"}":"object"==typeof a?o+=x(a,e?e.replace(/([^,])+/g,(t=>i.replace(/([^,]*:\S+\([^)]*\))|([^,])+/g,(e=>/&/.test(e)?e.replace(/&/g,t):t?t+" "+e:e)))):i):null!=a&&(i=/^--/.test(i)?i:i.replace(/[A-Z]/g,"-$&").toLowerCase(),r+=x.p?x.p(i,a):i+":"+a+";")}return n+(e&&r?e+"{"+r+"}":r)+o},C={},S=t=>{if("object"==typeof t){let e="";for(let n in t)e+=n+S(t[n]);return e}return t},O=(t,e,n,o,r)=>{let i=S(t),a=C[i]||(C[i]=(t=>{let e=0,n=11;for(;e<t.length;)n=101*n+t.charCodeAt(e++)>>>0;return"go"+n})(i));if(!C[a]){let e=i!==t?t:(t=>{let e,n,o=[{}];for(;e=w.exec(t.replace(b,""));)e[4]?o.shift():e[3]?(n=e[3].replace(v," ").trim(),o.unshift(o[0][n]=o[0][n]||{})):o[0][e[1]]=e[2].replace(v," ").trim();return o[0]})(t);C[a]=x(r?{["@keyframes "+a]:e}:e,n?"":"."+a)}let s=n&&C.g?C.g:null;return n&&(C.g=C[a]),((t,e,n,o)=>{o?e.data=e.data.replace(o,t):-1===e.data.indexOf(t)&&(e.data=n?t+e.data:e.data+t)})(C[a],e,o,s),a};function E(t){let e=this||{},n=t.call?t(e.p):t;return O(n.unshift?n.raw?((t,e,n)=>t.reduce(((t,o,r)=>{let i=e[r];if(i&&i.call){let t=i(n),e=t&&t.props&&t.props.className||/^go/.test(t)&&t;i=e?"."+e:t&&"object"==typeof t?t.props?"":x(t,""):!1===t?"":t}return t+o+(null==i?"":i)}),""))(n,[].slice.call(arguments,1),e.p):n.reduce(((t,n)=>Object.assign(t,n&&n.call?n(e.p):n)),{}):n,h(e.target),e.g,e.o,e.k)}E.bind({g:1});let j,A,P,I=E.bind({k:1});function k(t,e){let n=this||{};return function(){let o=arguments;function r(i,a){let s=Object.assign({},i),c=s.className||r.className;n.p=Object.assign({theme:A&&A()},s),n.o=/ *go\d+/.test(c),s.className=E.apply(n,o)+(c?" "+c:""),e&&(s.ref=a);let l=t;return t[0]&&(l=s.as||t,delete s.as),P&&l[0]&&P(s),j(l,s)}return e?e(r):r}}var $=(t,e)=>(t=>"function"==typeof t)(t)?t(e):t,F=(()=>{let t=0;return()=>(++t).toString()})(),_=(()=>{let t;return()=>{if(void 0===t&&typeof window<"u"){let e=matchMedia("(prefers-reduced-motion: reduce)");t=!e||e.matches}return t}})(),L=(t,e)=>{switch(e.type){case 0:return{...t,toasts:[e.toast,...t.toasts].slice(0,20)};case 1:return{...t,toasts:t.toasts.map((t=>t.id===e.toast.id?{...t,...e.toast}:t))};case 2:let{toast:n}=e;return L(t,{type:t.toasts.find((t=>t.id===n.id))?1:0,toast:n});case 3:let{toastId:o}=e;return{...t,toasts:t.toasts.map((t=>t.id===o||void 0===o?{...t,dismissed:!0,visible:!1}:t))};case 4:return void 0===e.toastId?{...t,toasts:[]}:{...t,toasts:t.toasts.filter((t=>t.id!==e.toastId))};case 5:return{...t,pausedAt:e.time};case 6:let r=e.time-(t.pausedAt||0);return{...t,pausedAt:void 0,toasts:t.toasts.map((t=>({...t,pauseDuration:t.pauseDuration+r})))}}},T=[],U={toasts:[],pausedAt:void 0},D=t=>{U=L(U,t),T.forEach((t=>{t(U)}))},R=t=>(e,n)=>{let o=((t,e="blank",n)=>({createdAt:Date.now(),visible:!0,dismissed:!1,type:e,ariaProps:{role:"status","aria-live":"polite"},message:t,pauseDuration:0,...n,id:(null==n?void 0:n.id)||F()}))(e,t,n);return D({type:2,toast:o}),o.id},N=(t,e)=>R("blank")(t,e);N.error=R("error"),N.success=R("success"),N.loading=R("loading"),N.custom=R("custom"),N.dismiss=t=>{D({type:3,toastId:t})},N.remove=t=>D({type:4,toastId:t}),N.promise=(t,e,n)=>{let o=N.loading(e.loading,{...n,...null==n?void 0:n.loading});return"function"==typeof t&&(t=t()),t.then((t=>{let r=e.success?$(e.success,t):void 0;return r?N.success(r,{id:o,...n,...null==n?void 0:n.success}):N.dismiss(o),t})).catch((t=>{let r=e.error?$(e.error,t):void 0;r?N.error(r,{id:o,...n,...null==n?void 0:n.error}):N.dismiss(o)})),t},new Map;var z=I`
from {
  transform: scale(0) rotate(45deg);
	opacity: 0;
}
to {
 transform: scale(1) rotate(45deg);
  opacity: 1;
}`,M=I`
from {
  transform: scale(0);
  opacity: 0;
}
to {
  transform: scale(1);
  opacity: 1;
}`,H=I`
from {
  transform: scale(0) rotate(90deg);
	opacity: 0;
}
to {
  transform: scale(1) rotate(90deg);
	opacity: 1;
}`,W=k("div")`
  width: 20px;
  opacity: 0;
  height: 20px;
  border-radius: 10px;
  background: ${t=>t.primary||"#ff4b4b"};
  position: relative;
  transform: rotate(45deg);

  animation: ${z} 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)
    forwards;
  animation-delay: 100ms;

  &:after,
  &:before {
    content: '';
    animation: ${M} 0.15s ease-out forwards;
    animation-delay: 150ms;
    position: absolute;
    border-radius: 3px;
    opacity: 0;
    background: ${t=>t.secondary||"#fff"};
    bottom: 9px;
    left: 4px;
    height: 2px;
    width: 12px;
  }

  &:before {
    animation: ${H} 0.15s ease-out forwards;
    animation-delay: 180ms;
    transform: rotate(90deg);
  }
`,q=I`
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
`,B=k("div")`
  width: 12px;
  height: 12px;
  box-sizing: border-box;
  border: 2px solid;
  border-radius: 100%;
  border-color: ${t=>t.secondary||"#e0e0e0"};
  border-right-color: ${t=>t.primary||"#616161"};
  animation: ${q} 1s linear infinite;
`,J=I`
from {
  transform: scale(0) rotate(45deg);
	opacity: 0;
}
to {
  transform: scale(1) rotate(45deg);
	opacity: 1;
}`,K=I`
0% {
	height: 0;
	width: 0;
	opacity: 0;
}
40% {
  height: 0;
	width: 6px;
	opacity: 1;
}
100% {
  opacity: 1;
  height: 10px;
}`,Y=k("div")`
  width: 20px;
  opacity: 0;
  height: 20px;
  border-radius: 10px;
  background: ${t=>t.primary||"#61d345"};
  position: relative;
  transform: rotate(45deg);

  animation: ${J} 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)
    forwards;
  animation-delay: 100ms;
  &:after {
    content: '';
    box-sizing: border-box;
    animation: ${K} 0.2s ease-out forwards;
    opacity: 0;
    animation-delay: 200ms;
    position: absolute;
    border-right: 2px solid;
    border-bottom: 2px solid;
    border-color: ${t=>t.secondary||"#fff"};
    bottom: 6px;
    left: 6px;
    height: 10px;
    width: 6px;
  }
`,G=k("div")`
  position: absolute;
`,V=k("div")`
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-width: 20px;
  min-height: 20px;
`,Z=I`
from {
  transform: scale(0.6);
  opacity: 0.4;
}
to {
  transform: scale(1);
  opacity: 1;
}`,Q=k("div")`
  position: relative;
  transform: scale(0.6);
  opacity: 0.4;
  min-width: 20px;
  animation: ${Z} 0.3s 0.12s cubic-bezier(0.175, 0.885, 0.32, 1.275)
    forwards;
`,X=({toast:t})=>{let{icon:e,type:n,iconTheme:o}=t;return void 0!==e?"string"==typeof e?s.createElement(Q,null,e):e:"blank"===n?null:s.createElement(V,null,s.createElement(B,{...o}),"loading"!==n&&s.createElement(G,null,"error"===n?s.createElement(W,{...o}):s.createElement(Y,{...o})))},tt=t=>`\n0% {transform: translate3d(0,${-200*t}%,0) scale(.6); opacity:.5;}\n100% {transform: translate3d(0,0,0) scale(1); opacity:1;}\n`,et=t=>`\n0% {transform: translate3d(0,0,-1px) scale(1); opacity:1;}\n100% {transform: translate3d(0,${-150*t}%,-1px) scale(.6); opacity:0;}\n`,nt=k("div")`
  display: flex;
  align-items: center;
  background: #fff;
  color: #363636;
  line-height: 1.3;
  will-change: transform;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1), 0 3px 3px rgba(0, 0, 0, 0.05);
  max-width: 350px;
  pointer-events: auto;
  padding: 8px 10px;
  border-radius: 8px;
`,ot=k("div")`
  display: flex;
  justify-content: center;
  margin: 4px 10px;
  color: inherit;
  flex: 1 1 auto;
  white-space: pre-line;
`;s.memo((({toast:t,position:e,style:n,children:o})=>{let r=t.height?((t,e)=>{let n=t.includes("top")?1:-1,[o,r]=_()?["0%{opacity:0;} 100%{opacity:1;}","0%{opacity:1;} 100%{opacity:0;}"]:[tt(n),et(n)];return{animation:e?`${I(o)} 0.35s cubic-bezier(.21,1.02,.73,1) forwards`:`${I(r)} 0.4s forwards cubic-bezier(.06,.71,.55,1)`}})(t.position||e||"top-center",t.visible):{opacity:0},i=s.createElement(X,{toast:t}),a=s.createElement(ot,{...t.ariaProps},$(t.message,t));return s.createElement(nt,{className:t.className,style:{...r,...n,...t.style}},"function"==typeof o?o({icon:i,message:a}):s.createElement(s.Fragment,null,i,a))})),function(t){x.p=void 0,j=t,A=void 0,P=void 0}(s.createElement),E`
  z-index: 9999;
  > * {
    pointer-events: auto;
  }
`;const rt={setOnLoadError:t=>{},setOnLoaderStart:t=>{}},it={payments:{setDefaultFilters:t=>{}},"payment-details":{setPayment:t=>{},setOnClose:t=>{}},"account-onboarding":{setFullTermsOfServiceUrl:t=>{},setRecipientTermsOfServiceUrl:t=>{},setPrivacyPolicyUrl:t=>{},setSkipTermsOfServiceCollection:t=>{},setCollectionOptions:t=>{},setOnExit:t=>{},setOnStepChange:t=>{}},"account-management":{setCollectionOptions:t=>{}},"notification-banner":{setCollectionOptions:t=>{},setOnNotificationsChange:t=>{}},"issuing-card":{setDefaultCard:t=>{},setCardSwitching:t=>{},setFetchEphemeralKey:t=>{},setShowSpendControls:t=>{}},"issuing-cards-list":{setFetchEphemeralKey:t=>{},setShowSpendControls:t=>{},setIssuingProgram:t=>{}},"financial-account":{setFinancialAccount:t=>{}},"financial-account-transactions":{setFinancialAccount:t=>{}},recipients:{setDataSource:t=>{}},"app-install":{setApp:t=>{},setOnAppInstallStateFetched:t=>{},setOnAppInstallStateChanged:t=>{}},"app-viewport":{setApp:t=>{},setAppData:t=>{}},"payment-method-settings":{setPaymentMethodConfiguration:t=>{}},"capital-financing":{setDefaultFinancingOffer:t=>{},setShowFinancingSelector:t=>{},setHowCapitalWorksUrl:t=>{},setSupportUrl:t=>{},setOnFinancingsLoaded:t=>{}},"capital-financing-application":{setOnApplicationSubmitted:t=>{},setPrivacyPolicyUrl:t=>{},setHowCapitalWorksUrl:t=>{}},"capital-financing-promotion":{setLayout:t=>{},setOnApplicationSubmitted:t=>{},setOnEligibleFinancingOfferLoaded:t=>{},setPrivacyPolicyUrl:t=>{},setHowCapitalWorksUrl:t=>{},setEligibilityCriteriaUrl:t=>{}},"reporting-chart":{setReportName:t=>{},setIntervalStart:t=>{},setIntervalEnd:t=>{},setIntervalType:t=>{}},"tax-settings":{setHideProductTaxCodeSelector:t=>{},setDisplayHeadOfficeCountries:t=>{},setOnTaxSettingsUpdated:t=>{}},"tax-registrations":{setOnAfterTaxRegistrationAdded:t=>{},setDisplayCountries:t=>{}}},at={payments:"stripe-connect-payments",payouts:"stripe-connect-payouts","payment-details":"stripe-connect-payment-details","account-onboarding":"stripe-connect-account-onboarding","payment-method-settings":"stripe-connect-payment-method-settings","account-management":"stripe-connect-account-management","notification-banner":"stripe-connect-notification-banner","instant-payouts":"stripe-connect-instant-payouts","issuing-card":"stripe-connect-issuing-card","issuing-cards-list":"stripe-connect-issuing-cards-list","financial-account":"stripe-connect-financial-account",recipients:"stripe-connect-recipients","financial-account-transactions":"stripe-connect-financial-account-transactions","capital-financing":"stripe-connect-capital-financing","capital-financing-application":"stripe-connect-capital-financing-application","capital-financing-promotion":"stripe-connect-capital-financing-promotion","capital-overview":"stripe-connect-capital-overview",documents:"stripe-connect-documents","tax-registrations":"stripe-connect-tax-registrations","tax-settings":"stripe-connect-tax-settings",balances:"stripe-connect-balances","payouts-list":"stripe-connect-payouts-list","app-install":"stripe-connect-app-install","app-viewport":"stripe-connect-app-viewport","reporting-chart":"stripe-connect-reporting-chart"},st="loadConnect was called but an existing Connect.js script already exists in the document; existing script parameters will be used",ct="https://connect-js.stripe.com/v1.0/connect.js";let lt=null;const pt=(t,e)=>{var n;const o=(()=>{try{return e.fetchClientSecret()}catch(t){return Promise.reject(t)}})(),r=null!==(n=e.metaOptions)&&void 0!==n?n:{},i=t.then((t=>t.initialize(Object.assign(Object.assign({},e),{metaOptions:Object.assign(Object.assign({},r),{eagerClientSecretPromise:o})}))));return{create:t=>{let e=at[t];e||(e=t);const n=document.createElement(e),o=(t=>t in it)(t)?it[t]:{},r=Object.assign(Object.assign({},o),rt);for(const t in r)n[t]=function(e){i.then((()=>{this[`${t}InternalOnly`](e)}))};return i.then((e=>{if(!n.isConnected&&!n.setConnector){const t=n.style.display;n.style.display="none",document.body.appendChild(n),document.body.removeChild(n),n.style.display=t}if(!n||!n.setConnector)throw new Error(`Element ${t} was not transformed into a custom element. Are you using a documented component? See https://docs.stripe.com/connect/supported-embedded-components for a list of supported components`);n.setConnector(e.connect)})),n},update:t=>{i.then((e=>{e.update(t)}))},debugInstance:()=>i,logout:()=>i.then((t=>t.logout()))}},dt=t=>(window.StripeConnect=window.StripeConnect||{},window.StripeConnect.optimizedLoading=!0,{initialize:e=>{var n;const o=null!==(n=e.metaOptions)&&void 0!==n?n:{};return t.init(Object.assign(Object.assign({},e),{metaOptions:Object.assign(Object.assign({},o),{sdk:!0,sdkOptions:{sdkVersion:"3.3.22-preview-2"}})}))}}),ut=Promise.resolve().then((()=>(null!==lt||(lt=new Promise(((t,e)=>{if("undefined"!=typeof window)if(window.StripeConnect&&console.warn(st),window.StripeConnect){const e=dt(window.StripeConnect);t(e)}else try{let n=document.querySelectorAll('script[src="https://connect-js.stripe.com/v0.1/connect.js"]')[0]||document.querySelectorAll(`script[src="${ct}"]`)[0]||null;n?console.warn(st):n||(n=(()=>{const t=document.createElement("script");if(t.src=ct,!document.head)throw new Error("Expected document.head not to be null. Connect.js requires a <head> element.");return document.head.appendChild(t),t})()),n.addEventListener("load",(()=>{if(window.StripeConnect){const e=dt(window.StripeConnect);t(e)}else e(new Error("Connect.js did not load the necessary objects"))})),n.addEventListener("error",(()=>{e(new Error("Failed to load Connect.js"))}))}catch(t){e(t)}else e("ConnectJS won't load when rendering code in the server - it can only be loaded on a browser. This error is expected when loading ConnectJS in SSR environments, like NextJS. It will have no impact in the UI, however if you wish to avoid it, you can switch to the `pure` version of the connect.js loader: https://github.com/stripe/connect-js#importing-loadconnect-without-side-effects.")}))),lt)));let ft=!1;ut.catch((t=>{ft||console.warn(t)}));const mt=window.wp.apiFetch;var yt=n.n(mt);const gt=window.wp.i18n;function ht(t){const e=window.stellarPayDashboardData;return e&&e[t]?e[t]:null}async function wt(t={}){const e=function(){const{live:{createAccountSessionURL:t},test:{createAccountSessionURL:e,isTestModeOnlyAccount:n}}=ht("stripe");return!0===ht("settings")["test-mode"]&&n?e:t}(),n=await yt()({url:e,method:"GET"}),{platformPublishableKey:o,clientSecret:r}=n,i={overlays:"dialog",variables:{colorPrimary:"#374151",buttonPrimaryColorText:"#FFF",actionPrimaryColorText:"#374151",spacingUnit:"10px",headingMdFontWeight:"500"},...t};return ft=!0,pt(ut,{publishableKey:o,fetchClientSecret:()=>r,appearance:i})}window.wp.htmlEntities;const bt=window.wp.components,vt=()=>{const[t,e]=(0,a.useState)(!1),{isCreatingAccountSession:n,stripeConnectInstance:r}=((t={})=>{const[e,n]=(0,a.useState)(!0),[o,r]=(0,a.useState)(null);return(0,a.useEffect)((()=>{(async()=>{n(!0);const e=await wt(t);r(e),n(!1)})()}),[JSON.stringify(t)]),{isCreatingAccountSession:e,stripeConnectInstance:o}})(),i=ht("transactionID");return(0,o.jsxs)("div",{children:[(0,o.jsx)(bt.Button,{variant:"secondary",onClick:t=>{t.preventDefault(),e(!0)},isBusy:n,children:(0,gt.__)("Show payment in Stripe","stellarpay")}),t&&(0,o.jsx)(u,{connectInstance:r,children:t&&(0,o.jsx)(y,{payment:i,onClose:()=>{e(!1)}})})]})};i()((()=>{(0,a.createRoot)(document.getElementById("stellarpay-stripe-payment-details-root")).render((0,o.jsx)(vt,{}))}))})();