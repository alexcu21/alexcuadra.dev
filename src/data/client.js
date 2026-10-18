import { gql } from 'graphql-request';
import { request, cmsFailure } from './cms-request.js';
import { publicProjects } from './public-projects.js';
import { isPublicContent, publicCollection } from './content-visibility.js';

// GraphQL endpoint
const endpoint = 'https://content.alexcuadra.dev/graphql';

export const getHomeInfo = async () => {

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

export const getProjectsInfo = async () => {
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
                    tags {
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

export const getArticlesCategories = async () => {
    const query = gql`
        query getArticleCategories {
            categories(where: {hideEmpty: true}) {
                nodes {
                        databaseId
                        count
                        name
                        slug
                        id
                    }
                }
            }

    `;

    const data = await request(endpoint, query, undefined, {
        publicCache: true,
        validatePublicData: data => Array.isArray(data?.categories?.nodes),
    });
    return data.categories.nodes;
}

export const getArticlesByCategory = async (slug) => {
    const query = gql`
    query getArticlesByCategory ($slug: String!){
        articles(where: {categoryName: $slug, status: PUBLISH, hasPassword: false}) {
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
            }
        }
    `;

    const variables = { slug };
    const { articles } = await request(endpoint, query, variables, {
        validateServiceData: data => publicCollection(data?.articles?.nodes),
    });
    return articles.nodes;
}

export const getArticleBySlug = async (slug) => {
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
                categories {
                    nodes {
                        id
                        name
                        slug
                    }
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
    return data.article;
}

export async function getAllArticleSlugs() {
    const query = gql`
      query AllArticles {
        articles(first: 100, where: {status: PUBLISH, hasPassword: false}) {
          nodes {
            status
            isRestricted
            hasPassword
            slug
          }
        }
      }
    `;
    const data = await request(endpoint, query, undefined, {
        validateServiceData: data => publicCollection(data?.articles?.nodes),
    });
    return data.articles.nodes.map((node) => node.slug);
  }

  export async function getAboutFields(){

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
