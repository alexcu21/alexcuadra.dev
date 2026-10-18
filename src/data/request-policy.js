export function isWordPressRestProbe(url) {
  // This host serves a portfolio, not the WordPress REST API.
  return url.searchParams.has('rest_route');
}
