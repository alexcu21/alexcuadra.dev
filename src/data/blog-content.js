import{sanitizeWordPressHtml}from './wordpress-html.js';
import{read,common}from './emdash-provider.js';
export async function hasBlogPosts(locale){return(await read('availability','posts',locale)).available;}
export async function getBlogPosts(locale){return(await read('list','posts',locale,{limit:'20'})).map(common).sort((a,b)=>Date.parse(b.dateGmt)-Date.parse(a.dateGmt));}
export async function getBlogPost(slug,locale){const d=await read('detail','posts',locale,{slug});return d?common(d):null;}
export function blogSummary(html) {
  return sanitizeWordPressHtml(html).replace(/<[^>]*>/g, ' ').replace(/&nbsp;|&#160;/g, ' ')
    .replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#0*39;|&apos;/g, "'")
    .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
    .replace(/&#(x[0-9a-f]+|[0-9]+);/gi, (_entity, digits) => {
      const point = digits[0].toLowerCase() === 'x' ? parseInt(digits.slice(1), 16) : Number(digits);
      return point > 0 && point <= 0x10ffff && !(point >= 0xd800 && point <= 0xdfff) ? String.fromCodePoint(point) : '';
    }).replace(/\s+/g, ' ').trim();
}

export function blogDate(value, locale) {
  if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z)?$/.test(value)) return null;
  const iso = value.endsWith('Z') ? value : value + 'Z';
  const date = new Date(iso);
  if (!Number.isFinite(date.getTime()) || date.toISOString().slice(0, 19) !== iso.slice(0, 19)) return null;
  return { iso, label: new Intl.DateTimeFormat(locale, { dateStyle: 'long', timeZone: 'UTC' }).format(date) };
}
