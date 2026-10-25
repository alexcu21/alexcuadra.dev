import { gql } from 'graphql-request';
import { collectCmsPages } from './cms-pagination.js';
import { request, cmsFailure } from './cms-request.js';
import { publicProjects } from './public-projects.js';
import { isPublicContent, publicCollection } from './content-visibility.js';
import { localizedCollection, localizedArticle, localizedCategories } from './localized-content.js';
import { localizedPage } from './localized-pages.js';

// GraphQL endpoint
const endpoint = 'https://content.alexcuadra.dev/graphql';

export const getHomeInfo = async (locale) => {
    if (locale !== undefined) {
        const page = await localizedPage('home', locale, 19);
        if (!page) throw cmsFailure('CMS_HOME_MISSING', 'LocalizedHome');
        return [page];
    }

    // Define the GraphQL query
    const query = gql`
        query getHomeFields {
            viewer { databaseId }
            pages(where: {id: 19, status: PUBLISH, hasPassword: false}) {
                nodes {
                    status
                    isRestricted
                    hasPassword
                    title
                    homePageFields {
                    fieldGroupName
                    name_field
                    photo_field {
                        node {
                        link
                        sourceUrl
                        }
                    }
                        role1_field
                        role2_field
                        role3_field
                        tagLine_field
                        twitterUrl
                        youtubeUrl
                        githubUrl
                        linkedinUrl
                        email
                        skills {
                            skillDescription1
                            skillDescription2
                            skillDescription3
                            skillName1
                            skillName2
                            skillName3
                        }
                    }
                }
            }
        }
    `;

    const data = await request(endpoint, query, undefined, {
        verifyServiceIdentity: true,
        validateServiceData: data => publicCollection(data?.pages?.nodes),
        publicCache: true,
        validatePublicData: data => Boolean(data?.pages?.nodes?.[0]?.homePageFields),
    });
    const info = data.pages?.nodes;
    if (!info?.[0]?.homePageFields) {
        throw cmsFailure('CMS_HOME_MISSING', 'getHomeFields');
    }
    return info;
}

export const getProjectsInfo = async (locale) => {
    if (locale !== undefined) return localizedCollection('projects', locale);
    const query = gql `
        query getProjectFields {
            projects(where: {status: PUBLISH, hasPassword: false}) {
                nodes {
                    status
                    isRestricted
                    hasPassword
                    title
                        projects {
                            githuburl
                            projectTagline
                            websiteurl
                        }
                    tags: projectTechnologies(first: 100) {
                        nodes {
                        name
                        }
                    }
                    featuredImage {
                        node {
                        sourceUrl
                        }
                    }
                    content
                    slug
                    modified
                }
            }
        }
    `
    const projectData = await request(endpoint, query, undefined, {
        publicCache: true,
        servicePublicProjection: publicProjects,
        validatePublicData: data => Array.isArray(data?.projects?.nodes),
    });
    return projectData.projects.nodes;
}

//Articles Data

export const getArticlesCategories = async (locale) => {
    if (locale !== undefined) return localizedCategories(locale);
    const query = gql`
        query getArticleCategories($after: String) {
            categories(first: 100, after: $after, where: {hideEmpty: true}) {
                nodes {
                        databaseId
                        count
                        name
                        slug
                        id
                    }
                    pageInfo { hasNextPage endCursor }
                }
            }

    `;

    return collectCmsPages(async after => {
        const data = await request(endpoint, query, { after }, {
            publicCache: true,
            validatePublicData: data => Array.isArray(data?.categories?.nodes)
                && typeof data.categories.pageInfo?.hasNextPage === 'boolean',
        });
        return data.categories;
    }, 'getArticleCategories');
}

export const getArticlesByCategory = async (slug, locale) => {
    if (locale !== undefined) return localizedCollection('articles', locale, slug);
    const query = gql`
    query getArticlesByCategory ($slug: String!, $after: String){
        articles(first: 100, after: $after, where: {categoryName: $slug, status: PUBLISH, hasPassword: false}) {
                nodes {
                    status
                    isRestricted
                    hasPassword
                    slug
                    title
                    content
                    id
                    featuredImage {
                        node {
                            sourceUrl
                        }
                    }
                }
                pageInfo { hasNextPage endCursor }
            }
        }
    `;

    return collectCmsPages(async after => {
        const data = await request(endpoint, query, { slug, after }, {
            validateServiceData: data => publicCollection(data?.articles?.nodes),
        });
        return data.articles;
    }, 'getArticlesByCategory');
}

export const getArticleBySlug = async (slug, locale) => {
    if (locale !== undefined) return localizedArticle(slug, locale);
    const query = gql`

        query getArticleBySlug ($slug: ID!){
            article(id: $slug, idType: SLUG) {
                status
                isRestricted
                hasPassword
                content
                featuredImage {
                    node {
                        sourceUrl
                    }
                }
                slug
                title
                id
                categories(first: 100) {
                    nodes {
                        id
                        name
                        slug
                    }
                    pageInfo { hasNextPage endCursor }
                }
                    articlesFields {
                        quote
                    }
            }
        }

    `;

    const variables = { slug };
    const data = await request(endpoint, query, variables, {
        validateServiceData: data => data?.article === null || isPublicContent(data?.article),
    });
    if (!data.article || !data.article.categories) return data.article;
    const categories = await collectCmsPages(async after => {
        if (after === null) return data.article.categories;
        const next = await request(endpoint, gql`
            query ArticleCategoryPage($slug: ID!, $after: String) {
                article(id: $slug, idType: SLUG) {
                    status isRestricted hasPassword
                    categories(first: 100, after: $after) {
                        nodes { id name slug }
                        pageInfo { hasNextPage endCursor }
                    }
                }
            }
        `, { slug, after }, {
            validateServiceData: result => isPublicContent(result?.article),
        });
        return next.article?.categories;
    }, 'ArticleCategoryPage');
    return { ...data.article, categories: { ...data.article.categories, nodes: categories } };
}

export async function getAllArticleSlugs() {
    const query = gql`
      query AllArticles($after: String) {
        articles(first: 100, after: $after, where: {status: PUBLISH, hasPassword: false}) {
          nodes {
            status
            isRestricted
            hasPassword
            slug
          }
          pageInfo { hasNextPage endCursor }
        }
      }
    `;
    const nodes = await collectCmsPages(async after => {
        const data = await request(endpoint, query, { after }, {
            validateServiceData: data => publicCollection(data?.articles?.nodes),
        });
        return data.articles;
    }, 'AllArticles');
    return nodes.map(node => node.slug);
  }

  export async function getAboutFields(locale){
    if (locale !== undefined) {
        const page = await localizedPage('about', locale, 68);
        if (!page) throw cmsFailure('CMS_ABOUT_MISSING', 'LocalizedAbout');
        return page;
    }

    const query = gql`

    query getAboutFields {
        pages(where: {id: 68, status: PUBLISH, hasPassword: false}) {
                edges {
                    node {
                        status
                        isRestricted
                        hasPassword
                        id
                            aboutPage {
                                education
                                introText
                                skillsText
                            }
                        content
                        featuredImage {
                            node {
                                sourceUrl
                            }
                        }
                    }
                }
            }
        }

    `;
    const data = await request(endpoint, query, undefined, {
        validateServiceData: data => Array.isArray(data?.pages?.edges)
            && publicCollection(data.pages.edges.map(edge => edge?.node)),
    });
    if (!data.pages?.edges?.[0]?.node) throw cmsFailure('CMS_ABOUT_MISSING', 'getAboutFields');
    return data.pages.edges[0].node;
  }
