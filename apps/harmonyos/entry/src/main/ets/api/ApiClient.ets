// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import http from '@ohos.net.http';

export class ApiClient {
  private static baseUrl: string = 'http://127.0.0.1:8788/api';
  private static token: string = '';
  static readonly platform: string = 'harmonyos';

  static setToken(t: string) { this.token = t; }
  static setBaseUrl(url: string) { this.baseUrl = url; }

  static getToken(): string { return this.token; }
  static getBaseUrl(): string { return this.baseUrl; }

  static async get(path: string): Promise<Object> {
    let req = http.createHttp();
    let resp = await req.request(this.baseUrl + path, {
      method: http.RequestMethod.GET,
      header: { 'X-API-Version': 'v1', 'X-Client-Platform': 'harmonyos', 'Authorization': `Bearer ${this.token}` }
    });
    let data = JSON.parse(resp.result as string);
    return data.data;
  }

  static async post(path: string, body: Object): Promise<Object> {
    let req = http.createHttp();
    let resp = await req.request(this.baseUrl + path, {
      method: http.RequestMethod.POST,
      header: { 'X-API-Version': 'v1', 'X-Client-Platform': 'harmonyos', 'Content-Type': 'application/json', 'Authorization': `Bearer ${this.token}` },
      extraData: JSON.stringify(body)
    });
    let data = JSON.parse(resp.result as string);
    return data.data;
  }

  static async put(path: string, body: Object): Promise<Object> {
    let req = http.createHttp();
    let resp = await req.request(this.baseUrl + path, {
      method: http.RequestMethod.PUT,
      header: { 'X-API-Version': 'v1', 'X-Client-Platform': 'harmonyos', 'Content-Type': 'application/json', 'Authorization': `Bearer ${this.token}` },
      extraData: JSON.stringify(body)
    });
    let data = JSON.parse(resp.result as string);
    return data.data;
  }

  static async delete(path: string): Promise<Object> {
    let req = http.createHttp();
    let resp = await req.request(this.baseUrl + path, {
      method: http.RequestMethod.DELETE,
      header: { 'X-API-Version': 'v1', 'X-Client-Platform': 'harmonyos', 'Authorization': `Bearer ${this.token}` }
    });
    let data = JSON.parse(resp.result as string);
    return data.data;
  }

  static async login(username: string, password: string): Promise<Object> {
    let req = http.createHttp();
    let resp = await req.request(this.baseUrl + '/auth/login', {
      method: http.RequestMethod.POST,
      header: { 'X-API-Version': 'v1', 'X-Client-Platform': 'harmonyos', 'Content-Type': 'application/json' },
      extraData: JSON.stringify({ username: username, password: password })
    });
    let data = JSON.parse(resp.result as string);
    if (data.data && data.data.token) {
      this.setToken(data.data.token);
    }
    return data.data;
  }
}
