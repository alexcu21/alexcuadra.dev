import { request, gql } from 'graphql-request';


// GraphQL endpoint
const endpoint = 'https://alexcuadra.dev/graphql';



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
        console.error('Error fetching data:', error);
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
    const projectData = await request(endpoint, query)
    let projectInfo = projectData.projects.nodes

    return projectInfo; 
}