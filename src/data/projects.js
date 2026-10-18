// WordPress returns ISO date strings; subtracting them produces NaN.
export function sortProjectsByModified(projects) {
  const timestamp = (value) => Date.parse(value) || 0;
  return [...projects].sort((a, b) => timestamp(b.modified) - timestamp(a.modified));
}
