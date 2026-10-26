import styles from '../../../../../public/error-pages.css?raw';
export const GET = () => new Response(styles, {headers:{'Content-Type':'text/css; charset=utf-8'}});
