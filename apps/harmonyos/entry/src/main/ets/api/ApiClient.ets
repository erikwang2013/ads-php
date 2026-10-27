// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import http from '@ohos.net.http';

export class ApiClient {
  // 默认指向本机开发服务：模拟器可通过宿主机端口映射直连 127.0.0.1；
  // 真机联调需改为宿主机局域网地址（如 http://192.168.1.100:8788/api/v1），
  // 可在运行时通过 ApiClient.setBaseUrl(url) 覆盖，无需改代码。
  // API 版本号固定在 URL 路径上（/api/v1/...）。
  private static baseUrl: string = 'http://127.0.0.1:8788/api/v1';
  private static token: string = '';
  static readonly platform: string = 'harmonyos';

  static setToken(t: string) { this.token = t; }
  static setBaseUrl(url: string) { this.baseUrl = url; }
  static getToken(): string { return this.token; }
  static getBaseUrl(): string { return this.baseUrl; }

  private static replayHeaders(): Record<string, string | number> {
    return {
      'X-Nonce': Date.now().toString(36) + Math.random().toString(36).slice(2, 8),
      'X-Timestamp': Math.floor(Date.now() / 1000)
    };
  }

  private static buildHeaders(needAuth: boolean): Record<string, string | number> {
    // replayHeaders 只展开一次（修复此前 get() 内重复展开两次的 bug）
    let headers: Record<string, string | number> = {
      ...this.replayHeaders(),
      'X-Client-Platform': this.platform
    };
    if (needAuth && this.token !== '') {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    return headers;
  }

  /**
   * 统一请求入口并解包 ApiResponse envelope：{code, message, data}。
   * - HTTP 非 2xx：抛 Error（优先取响应体中的 message）
   * - envelope.code !== 0：抛 Error（message 为服务端文案，可直接 toast）
   * - JSON 解析失败：抛 Error
   * - 成功：返回 envelope 的 data（与旧 get/post/put/delete 返回 data.data 的约定一致）
   */
  private static async request(path: string, method: http.RequestMethod, body?: Object): Promise<Object> {
    let req = http.createHttp();
    let options: http.HttpRequestOptions = {
      method: method,
      header: this.buildHeaders(path !== '/auth/login')
    };
    if (body !== undefined) {
      options.extraData = JSON.stringify(body);
    }
    let resp: http.HttpResponse;
    try {
      resp = await req.request(this.baseUrl + path, options);
    } catch (e) {
      throw new Error('网络请求失败，请检查网络连接或服务地址');
    } finally {
      req.destroy();
    }

    let bodyStr: string = resp.result as string;
    if (resp.responseCode < 200 || resp.responseCode >= 300) {
      let msg = this.extractMessage(bodyStr);
      throw new Error(msg !== '' ? msg : `请求失败 (HTTP ${resp.responseCode})`);
    }

    let parsed: Record<string, Object>;
    try {
      parsed = JSON.parse(bodyStr) as Record<string, Object>;
    } catch (e) {
      throw new Error('服务器响应格式错误');
    }

    let code: number = ('code' in parsed) ? Number(parsed['code']) : 0;
    if (code !== 0) {
      let msg = parsed['message'];
      throw new Error(msg === undefined ? '请求失败' : String(msg));
    }
    return parsed['data'] as Object;
  }

  private static extractMessage(body: string): string {
    try {
      let parsed = JSON.parse(body) as Record<string, Object>;
      if (parsed['message'] !== undefined) {
        return String(parsed['message']);
      }
    } catch (e) {
      // 非 JSON 响应体，无 message 可取
    }
    return '';
  }

  static async get(path: string): Promise<Object> {
    return this.request(path, http.RequestMethod.GET);
  }

  static async post(path: string, body: Object): Promise<Object> {
    return this.request(path, http.RequestMethod.POST, body);
  }

  static async put(path: string, body: Object): Promise<Object> {
    return this.request(path, http.RequestMethod.PUT, body);
  }

  static async delete(path: string): Promise<Object> {
    return this.request(path, http.RequestMethod.DELETE);
  }

  /**
   * 登录。service POST /api/v1/auth/login 成功返回
   * {access_token, token_type, expires_in, user}（见 AuthController::login，
   * 字段为 access_token 而非 token），成功后自动写入 token 供后续请求携带。
   */
  static async login(username: string, password: string): Promise<Object> {
    let data: Object = await this.request('/auth/login', http.RequestMethod.POST, {
      username: username,
      password: password
    });
    if (data !== undefined) {
      let d = data as Record<string, Object>;
      let token = d['access_token'];
      if (token !== undefined && token !== null) {
        this.setToken(String(token));
      }
    }
    return data;
  }
}
