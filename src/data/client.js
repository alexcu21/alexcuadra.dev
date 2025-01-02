import { request, gql } from 'graphql-request';

// GraphQL endpoint
const endpoint = process.env.APIURL;

export const getHomeInfo = async () => {

    // Define the GraphQL query
    const query = gql`
        query getHomeFields {
            pages(where: {id: 19}) {
                nodes {
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

    // Fetch data
    let info = [];

    try {
        const data = await request(endpoint, query);
        info = data.pages.nodes;

    } catch (error) {
        console.error('Error fetching Home data:', error);
    }

    return info;
}

export const getProjectsInfo = async () => {
    const query = gql `
        query getProjectFields {
            projects {
                nodes {
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
    let projectInfo = [];

    try{
        const projectData = await request(endpoint, query)
        projectInfo = projectData.projects.nodes

    }catch (error) {
        console.error('Error fetching Projects data:', error);
    }
    

    return projectInfo; 
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

    let categories =[];

    try{
        const articlesCategories = await request(endpoint, query);
        categories = articlesCategories.categories.nodes;
    }catch (error) {
        console.error('Error fetching categories data:', error);
    }
    return categories;
}

export const getArticlesByCategory = async (slug) => {
    const query = gql`
    query getArticlesByCategory ($slug: String!){
        articles(where: {categoryName: $slug}) {
                nodes {
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
    const { articles } = await request(endpoint, query, variables);
    return articles.nodes;
}

export const getArticleBySlug = async (slug) => {
    const query = gql`

        query getArticleBySlug ($slug: ID!){
            article(id: $slug, idType: SLUG) {
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
    const data = await request(endpoint, query, variables);
    return data.article;
}

export async function getAllArticleSlugs() {
    const query = gql`
      query AllArticles {
        articles(first: 100) {
          nodes {
            slug
          }
        }
      }
    `;
    const data = await request(endpoint, query);
    return data.articles.nodes.map((node) => node.slug);
  }

  export async function getAboutFields(){

    const query = gql`

    query getAboutFields {
        pages(where: {id: 68}) {
                edges {
                    node {
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
    const data = await request(endpoint, query)
    return data.pages.edges[0].node;
  }