<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased w-full p-0 m-0">
    <head>
        <title>Fattura-{{ $invoiceNumber }}</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Lexend:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        <style>
         *, ::before, ::after {
  --tw-border-spacing-x: 0;
  --tw-border-spacing-y: 0;
  --tw-translate-x: 0;
  --tw-translate-y: 0;
  --tw-rotate: 0;
  --tw-skew-x: 0;
  --tw-skew-y: 0;
  --tw-scale-x: 1;
  --tw-scale-y: 1;
  --tw-pan-x: ;
  --tw-pan-y: ;
  --tw-pinch-zoom: ;
  --tw-scroll-snap-strictness: proximity;
  --tw-gradient-from-position: ;
  --tw-gradient-via-position: ;
  --tw-gradient-to-position: ;
  --tw-ordinal: ;
  --tw-slashed-zero: ;
  --tw-numeric-figure: ;
  --tw-numeric-spacing: ;
  --tw-numeric-fraction: ;
  --tw-ring-inset: ;
  --tw-ring-offset-width: 0px;
  --tw-ring-offset-color: #fff;
  --tw-ring-color: rgb(59 130 246 / 0.5);
  --tw-ring-offset-shadow: 0 0 #0000;
  --tw-ring-shadow: 0 0 #0000;
  --tw-shadow: 0 0 #0000;
  --tw-shadow-colored: 0 0 #0000;
  --tw-blur: ;
  --tw-brightness: ;
  --tw-contrast: ;
  --tw-grayscale: ;
  --tw-hue-rotate: ;
  --tw-invert: ;
  --tw-saturate: ;
  --tw-sepia: ;
  --tw-drop-shadow: ;
  --tw-backdrop-blur: ;
  --tw-backdrop-brightness: ;
  --tw-backdrop-contrast: ;
  --tw-backdrop-grayscale: ;
  --tw-backdrop-hue-rotate: ;
  --tw-backdrop-invert: ;
  --tw-backdrop-opacity: ;
  --tw-backdrop-saturate: ;
  --tw-backdrop-sepia: ;
  --tw-contain-size: ;
  --tw-contain-layout: ;
  --tw-contain-paint: ;
  --tw-contain-style: ;
}
::backdrop {
  --tw-border-spacing-x: 0;
  --tw-border-spacing-y: 0;
  --tw-translate-x: 0;
  --tw-translate-y: 0;
  --tw-rotate: 0;
  --tw-skew-x: 0;
  --tw-skew-y: 0;
  --tw-scale-x: 1;
  --tw-scale-y: 1;
  --tw-pan-x: ;
  --tw-pan-y: ;
  --tw-pinch-zoom: ;
  --tw-scroll-snap-strictness: proximity;
  --tw-gradient-from-position: ;
  --tw-gradient-via-position: ;
  --tw-gradient-to-position: ;
  --tw-ordinal: ;
  --tw-slashed-zero: ;
  --tw-numeric-figure: ;
  --tw-numeric-spacing: ;
  --tw-numeric-fraction: ;
  --tw-ring-inset: ;
  --tw-ring-offset-width: 0px;
  --tw-ring-offset-color: #fff;
  --tw-ring-color: rgb(59 130 246 / 0.5);
  --tw-ring-offset-shadow: 0 0 #0000;
  --tw-ring-shadow: 0 0 #0000;
  --tw-shadow: 0 0 #0000;
  --tw-shadow-colored: 0 0 #0000;
  --tw-blur: ;
  --tw-brightness: ;
  --tw-contrast: ;
  --tw-grayscale: ;
  --tw-hue-rotate: ;
  --tw-invert: ;
  --tw-saturate: ;
  --tw-sepia: ;
  --tw-drop-shadow: ;
  --tw-backdrop-blur: ;
  --tw-backdrop-brightness: ;
  --tw-backdrop-contrast: ;
  --tw-backdrop-grayscale: ;
  --tw-backdrop-hue-rotate: ;
  --tw-backdrop-invert: ;
  --tw-backdrop-opacity: ;
  --tw-backdrop-saturate: ;
  --tw-backdrop-sepia: ;
  --tw-contain-size: ;
  --tw-contain-layout: ;
  --tw-contain-paint: ;
  --tw-contain-style: ;
}
/*
! tailwindcss v3.4.14 | MIT License | https://tailwindcss.com
*/
/*
1. Prevent padding and border from affecting element width. (https://github.com/mozdevs/cssremedy/issues/4)
2. Allow adding a border to an element by just adding a border-width. (https://github.com/tailwindcss/tailwindcss/pull/116)
*/
*,
::before,
::after {
  box-sizing: border-box;
  /* 1 */
  border-width: 0;
  /* 2 */
  border-style: solid;
  /* 2 */
  border-color: #e5e7eb;
  /* 2 */
}
::before,
::after {
  --tw-content: '';
}

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
    }
    .container {
        margin: 0;
        padding: 0;
        width: 100%;
    }

html,
:host {
  line-height: 1.5;
  /* 1 */
  -webkit-text-size-adjust: 100%;
  /* 2 */
  -moz-tab-size: 4;
  /* 3 */
  tab-size: 4;
  /* 3 */
  font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
  /* 4 */
  font-feature-settings: normal;
  /* 5 */
  font-variation-settings: normal;
  /* 6 */
  -webkit-tap-highlight-color: transparent;
  /* 7 */
}
/*
1. Remove the margin in all browsers.
2. Inherit line-height from `html` so users can set them as a class directly on the `html` element.
*/
body {
  margin: 0;
  /* 1 */
  line-height: inherit;
  /* 2 */
}
/*
1. Add the correct height in Firefox.
2. Correct the inheritance of border color in Firefox. (https://bugzilla.mozilla.org/show_bug.cgi?id=190655)
3. Ensure horizontal rules are visible by default.
*/
hr {
  height: 0;
  /* 1 */
  color: inherit;
  /* 2 */
  border-top-width: 1px;
  /* 3 */
}
/*
Add the correct text decoration in Chrome, Edge, and Safari.
*/
abbr:where([title]) {
  text-decoration: underline dotted;
}
/*
Remove the default font size and weight for headings.
*/
h1,
h2,
h3,
h4,
h5,
h6 {
  font-size: inherit;
  font-weight: inherit;
}
/*
Reset links to optimize for opt-in styling instead of opt-out.
*/
a {
  color: inherit;
  text-decoration: inherit;
}
/*
Add the correct font weight in Edge and Safari.
*/
b,
strong {
  font-weight: bolder;
}
/*
1. Use the user's configured `mono` font-family by default.
2. Use the user's configured `mono` font-feature-settings by default.
3. Use the user's configured `mono` font-variation-settings by default.
4. Correct the odd `em` font sizing in all browsers.
*/
code,
kbd,
samp,
pre {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  /* 1 */
  font-feature-settings: normal;
  /* 2 */
  font-variation-settings: normal;
  /* 3 */
  font-size: 1em;
  /* 4 */
}
/*
Add the correct font size in all browsers.
*/
small {
  font-size: 80%;
}
/*
Prevent `sub` and `sup` elements from affecting the line height in all browsers.
*/
sub,
sup {
  font-size: 75%;
  line-height: 0;
  position: relative;
  vertical-align: baseline;
}
sub {
  bottom: -0.25em;
}
sup {
  top: -0.5em;
}
/*
1. Remove text indentation from table contents in Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=999088, https://bugs.webkit.org/show_bug.cgi?id=201297)
2. Correct table border color inheritance in all Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=935729, https://bugs.webkit.org/show_bug.cgi?id=195016)
3. Remove gaps between table borders by default.
*/
table {
  text-indent: 0;
  /* 1 */
  border-color: inherit;
  /* 2 */
  border-collapse: collapse;
  /* 3 */
}
/*
1. Change the font styles in all browsers.
2. Remove the margin in Firefox and Safari.
3. Remove default padding in all browsers.
*/
button,
input,
optgroup,
select,
textarea {
  font-family: inherit;
  /* 1 */
  font-feature-settings: inherit;
  /* 1 */
  font-variation-settings: inherit;
  /* 1 */
  font-size: 100%;
  /* 1 */
  font-weight: inherit;
  /* 1 */
  line-height: inherit;
  /* 1 */
  letter-spacing: inherit;
  /* 1 */
  color: inherit;
  /* 1 */
  margin: 0;
  /* 2 */
  padding: 0;
  /* 3 */
}
/*
Remove the inheritance of text transform in Edge and Firefox.
*/
button,
select {
  text-transform: none;
}
/*
1. Correct the inability to style clickable types in iOS and Safari.
2. Remove default button styles.
*/
button,
input:where([type='button']),
input:where([type='reset']),
input:where([type='submit']) {
  -webkit-appearance: button;
  /* 1 */
  background-color: transparent;
  /* 2 */
  background-image: none;
  /* 2 */
}
/*
Use the modern Firefox focus style for all focusable elements.
*/
:-moz-focusring {
  outline: auto;
}
/*
Remove the additional `:invalid` styles in Firefox. (https://github.com/mozilla/gecko-dev/blob/2f9eacd9d3d995c937b4251a5557d95d494c9be1/layout/style/res/forms.css#L728-L737)
*/
:-moz-ui-invalid {
  box-shadow: none;
}
/*
Add the correct vertical alignment in Chrome and Firefox.
*/
progress {
  vertical-align: baseline;
}
/*
Correct the cursor style of increment and decrement buttons in Safari.
*/
::-webkit-inner-spin-button,
::-webkit-outer-spin-button {
  height: auto;
}
/*
1. Correct the odd appearance in Chrome and Safari.
2. Correct the outline style in Safari.
*/
[type='search'] {
  -webkit-appearance: textfield;
  /* 1 */
  outline-offset: -2px;
  /* 2 */
}
/*
Remove the inner padding in Chrome and Safari on macOS.
*/
::-webkit-search-decoration {
  -webkit-appearance: none;
}
/*
1. Correct the inability to style clickable types in iOS and Safari.
2. Change font properties to `inherit` in Safari.
*/
::-webkit-file-upload-button {
  -webkit-appearance: button;
  /* 1 */
  font: inherit;
  /* 2 */
}
/*
Add the correct display in Chrome and Safari.
*/
summary {
  display: list-item;
}
/*
Removes the default spacing and border for appropriate elements.
*/
blockquote,
dl,
dd,
h1,
h2,
h3,
h4,
h5,
h6,
hr,
figure,
p,
pre {
  margin: 0;
}
fieldset {
  margin: 0;
  padding: 0;
}
legend {
  padding: 0;
}
ol,
ul,
menu {
  list-style: none;
  margin: 0;
  padding: 0;
}
/*
Reset default styling for dialogs.
*/
dialog {
  padding: 0;
}
/*
Prevent resizing textareas horizontally by default.
*/
textarea {
  resize: vertical;
}
/*
1. Reset the default placeholder opacity in Firefox. (https://github.com/tailwindlabs/tailwindcss/issues/3300)
2. Set the default placeholder color to the user's configured gray 400 color.
*/
input::placeholder,
textarea::placeholder {
  opacity: 1;
  /* 1 */
  color: #9ca3af;
  /* 2 */
}
/*
Set the default cursor for buttons.
*/
button,
[role="button"] {
  cursor: pointer;
}
/*
Make sure disabled buttons don't get the pointer cursor.
*/
:disabled {
  cursor: default;
}
/*
1. Make replaced elements `display: block` by default. (https://github.com/mozdevs/cssremedy/issues/14)
2. Add `vertical-align: middle` to align replaced elements more sensibly by default. (https://github.com/jensimmons/cssremedy/issues/14#issuecomment-634934210)
   This can trigger a poorly considered lint error in some tools but is included by design.
*/
img,
svg,
video,
canvas,
audio,
iframe,
embed,
object {
  display: block;
  /* 1 */
  vertical-align: middle;
  /* 2 */
}
/*
Constrain images and videos to the parent width and preserve their intrinsic aspect ratio. (https://github.com/mozdevs/cssremedy/issues/14)
*/
img,
video {
  max-width: 100%;
  height: auto;
}
/* Make elements with the HTML hidden attribute stay hidden by default */
[hidden]:where(:not([hidden="until-found"])) {
  display: none;
}
.fixed {
  position: fixed;
}
.bottom-0 {
  bottom: 0px;
}
.left-0 {
  left: 0px;
}
.table {
  display: table;
}
.h-full {
  height: 100%;
}
.max-h-9 {
  max-height: 2.25rem;
}
.max-h-7 {
  max-height: 1.75rem;
}
.min-h-6 {
  min-height: 1.5rem;
}
.min-h-5 {
  min-height: 1.25rem;
}
.w-1\/2 {
  width: 50%;
}
.w-full {
  width: 100%;
}
.border-collapse {
  border-collapse: collapse;
}
.border-spacing-0 {
  --tw-border-spacing-x: 0px;
  --tw-border-spacing-y: 0px;
  border-spacing: var(--tw-border-spacing-x) var(--tw-border-spacing-y);
}
.whitespace-nowrap {
  white-space: nowrap;
}
.border-b {
  border-bottom-width: 1px;
}
.border-b-2 {
  border-bottom-width: 2px;
}
.border-r {
  border-right-width: 1px;
}
.border-blue-600 {
  --tw-border-opacity: 1;
  border-color: rgb(37 99 235 / var(--tw-border-opacity));
}
.bg-blue-600 {
  --tw-bg-opacity: 1;
  background-color: rgb(37 99 235 / var(--tw-bg-opacity));
}
.bg-slate-100 {
  background-color: #f3f3f5;
}
.p-3 {
  padding: 0.75rem;
}
.px-14 {
  padding-left: 3.5rem;
  padding-right: 3.5rem;
}
.px-2 {
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}
.py-10 {
  padding-top: 2.5rem;
  padding-bottom: 2.5rem;
}
.py-3 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}
.py-4 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}
.py-6 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}
.pb-3 {
  padding-bottom: 0.75rem;
}
.pl-2 {
  padding-left: 0.5rem;
}
.pl-3 {
  padding-left: 0.75rem;
}
.pl-4 {
  padding-left: 1rem;
}
.pr-4 {
  padding-right: 1rem;
}
.text-center {
  text-align: center;
}
.text-right {
  text-align: right;
}
.align-top {
  vertical-align: top;
}
.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}
.text-xs {
  font-size: 0.75rem;
  line-height: 1rem;
}
.font-bold {
  font-weight: 700;
}
.italic {
  font-style: italic;
}
.text-blue-600 {
  --tw-text-opacity: 1;
  color: rgb(37 99 235 / var(--tw-text-opacity));
}
.text-neutral-600 {
  --tw-text-opacity: 1;
  color: rgb(82 82 82 / var(--tw-text-opacity));
}
.text-neutral-700 {
  --tw-text-opacity: 1;
  color: rgb(64 64 64 / var(--tw-text-opacity));
}
.text-slate-300 {
  --tw-text-opacity: 1;
  color: rgb(203 213 225 / var(--tw-text-opacity));
}
.text-slate-400 {
  --tw-text-opacity: 1;
  color: rgb(148 163 184 / var(--tw-text-opacity));
}
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity));
}
.antialiased {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}


        </style>
        
    </head>
    <body class="h-full w-full p-0 m-0">

<div>
    <div class="py-4">
      <div class="px-14 py-6">
        <table class="w-full border-collapse border-spacing-0">
          <tbody>
            <tr>
              <td class="w-full">
                <div>
                 <img style="max-width: 10rem;" src="{{ $companyLogo }}" class="max-h-7" />
                </div>
              </td>

              <td class="align-top">
                <div class="text-sm">
                  <table class="border-collapse border-spacing-0">
                    <tbody>
                      <tr>
                        <td class="border-r pr-4">
                          <div>
                            <p class="whitespace-nowrap text-neutral-700 text-right">Data:</p>
                            <p class="whitespace-nowrap font-bold text-right" style="color: #0d172b" >{{ $invoiceDate }}</p>
                            </p>
                          </div>
                        </td>
                        <td class="pl-4">
                          <div>
                            <p class="whitespace-nowrap text-neutral-700 text-right">Fattura:</p>
                            <p class="whitespace-nowrap font-bold text-right" style="color: #0d172b" >{{ $invoiceNumber }}</p>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-slate-100 px-14 py-6 text-sm">
        <table class="w-full border-collapse border-spacing-0">
          <tbody>
            <tr>
              <td class="w-1/2 align-top">
                <div class="text-sm text-neutral-600">
                     <p style="color: #0d172b;" class="font-bold">{{ $companyName }}</p>
    <p>P.IVA: {{ $companyVat }}</p>
    
   {{ $companyReaBlock }}
{{ $companyEmailBlock }}
{{ $companyPecBlock }}
                </div>
              </td>
              <td class="w-1/2 align-top text-right">
                <div class="text-sm text-neutral-600">
                  <p>Fatturato a:</p>
                  <p style="color: #0d172b;" class="font-bold">{{ $clientName }}</p>
                  <p>P.IVA: {{ $clientPIVA }}</p>
                  <p>{{ $clientAddress }}</p>
                  <p>{{ $clientCAP }} {{ $clientCity }} ({{ $clientProvince }})</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

{{ $headerNotesBlock }}

      <div class="px-14 py-10 text-sm text-neutral-700">
        <table class="w-full border-collapse border-spacing-0">
          <thead>
            <tr>
              <td class="border-b-2 pb-3 pl-3 font-bold " style="border-color: #0d172b; color: #0d172b;" >#</td>
              <td class="border-b-2 pb-3 pl-2 font-bold " style="border-color: #0d172b; color: #0d172b;" >Dettaglio</td>
              <td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >Q.tà</td>
              <td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >Prezzo unitario</td>
              @if($company->regime_fiscale !== 'RF19')
                <td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >IVA (%)</td>
              @endif
              <td class="border-b-2 pb-3 pl-2 pr-4 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >Totale (IVA escl.)</td>
            </tr>
          </thead>
          <tbody>
            {{ $invoiceRows }}
            <tr>
              <td colspan="7">
                <table class="w-full border-collapse border-spacing-0">
                  <tbody>
                    <tr>
                      <td>
                        <table class="w-full border-collapse border-spacing-0">
                          <tbody>
             
                            <tr>
  <td class="border-b p-3 w-full"></td>
  <td class="border-b p-3">
    <div class="whitespace-nowrap text-neutral-700">Totale (IVA escl.):</div>
  </td>
  <td class="border-b p-3 text-right">
    <div class="whitespace-nowrap text-neutral-700">€{{ $subtotal }}</div>
  </td>
</tr>

<tr>
  <td class="border-b p-3 w-full"></td>
  <td class="border-b p-3">
    <div class="whitespace-nowrap text-neutral-700">Importo totale IVA:</div>
  </td>
  <td class="border-b p-3 text-right">
    <div class="whitespace-nowrap text-neutral-700">€{{ $vatTotal }}</div>
  </td>
</tr>

{{ $globalDiscountBlock }}

<tr>
  <td class="bg-slate-100 p-3 w-full"></td>
  <td class="bg-slate-100 p-3">
    <div class="whitespace-nowrap font-bold text-neutral-700">Totale (IVA inclusa):</div>
  </td>
  <td class="bg-slate-100 p-3 text-right">
    <div class="whitespace-nowrap font-bold text-neutral-700">€{{ $price }}</div>
  </td>
</tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
{{ $paymentMethodBlock }}
{{ $paymentScheduleBlock }}
{{ $footerNotesBlock }}        
{{ $forfettarioBlock }}  
      </div>
    </div>
    </body>
</html>
