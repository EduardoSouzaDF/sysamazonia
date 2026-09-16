"""Bounded HTTP transport for administrator-approved custom/local endpoints."""
import ipaddress
import socket
from urllib.parse import urlsplit

import httpx


class EndpointError(ValueError):
    pass


def validate_endpoint(value: str, provider: str, allowed: str) -> str:
    try:
        url = urlsplit(value)
        port = url.port
    except ValueError:
        raise EndpointError('Base URL inválida.') from None
    if (url.scheme not in ('http', 'https') or not url.hostname or url.username or url.password
            or url.query or url.fragment or '\\' in value or any(c.isspace() for c in value)
            or '%' in value or any(part in ('.', '..') for part in url.path.split('/'))):
        raise EndpointError('Base URL inválida; não inclua credenciais, query ou fragmento.')
    normalized = value.rstrip('/')
    approved = {item.strip().rstrip('/') for item in allowed.split(',') if item.strip()}
    if normalized not in approved:
        raise EndpointError('Base URL não autorizada na allowlist do serviço.')
    if provider == 'local':
        # Numeric private/loopback addresses only: no DNS rebinding or metadata endpoint.
        try:
            address = ipaddress.ip_address(url.hostname)
        except ValueError:
            raise EndpointError('Endpoint local deve usar IP privado/loopback literal autorizado.') from None
        networks = [ipaddress.ip_network(n) for n in ('127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '::1/128', 'fc00::/7')]
        if not any(address in network for network in networks):
            raise EndpointError('IP local não permitido.')
    elif url.scheme != 'https':
        raise EndpointError('Endpoint custom remoto exige HTTPS.')
    if port == 0:
        raise EndpointError('Porta inválida.')
    return normalized


class BoundedTransport(httpx.BaseTransport):
    """Reject redirects and cap decoded response bodies before SDK deserialization."""
    def __init__(self, limit: int = 2_000_000, public_only: bool = False):
        self.inner = httpx.HTTPTransport(retries=0)
        self.limit = limit
        self.public_only = public_only

    def handle_request(self, request: httpx.Request) -> httpx.Response:
        if self.public_only:
            try:
                addresses = [item[4][0] for item in socket.getaddrinfo(request.url.host, request.url.port or 443, type=socket.SOCK_STREAM)]
            except OSError:
                raise httpx.ConnectError('DNS do provider indisponível.') from None
            if not addresses or any(not ipaddress.ip_address(address).is_global for address in addresses):
                raise EndpointError('Provider remoto deve resolver somente para IPs públicos.')
            # Pin the validated address for this connection; keep TLS SNI and Host.
            headers = dict(request.headers)
            headers['host'] = request.url.netloc.decode('ascii')
            extensions = dict(request.extensions, sni_hostname=request.url.host)
            request = httpx.Request(request.method, request.url.copy_with(host=addresses[0]),
                headers=headers, stream=request.stream, extensions=extensions)
        response = self.inner.handle_request(request)
        try:
            if 300 <= response.status_code < 400:
                raise EndpointError('Redirect de provider bloqueado.')
            body = bytearray()
            for chunk in response.iter_bytes():
                body.extend(chunk)
                if len(body) > self.limit:
                    raise EndpointError('Resposta do provider excedeu o limite.')
            headers = dict(response.headers)
            headers.pop('content-encoding', None)
            headers.pop('content-length', None)
            return httpx.Response(response.status_code, headers=headers, content=bytes(body), request=request)
        finally:
            response.close()

    def close(self):
        self.inner.close()
