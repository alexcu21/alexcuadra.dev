import{hasBlogPosts}from './blog-content.js';
import{hasLabProjects}from './lab-content.js';
export async function navigationAvailability(locale,{labAvailable,blogAvailable}={}){return{blogAvailable:blogAvailable??await hasBlogPosts(locale),labAvailable:labAvailable??await hasLabProjects(locale)};}
