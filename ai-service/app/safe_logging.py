"""Provider SDK log messages may echo credentials or untrusted response bodies."""
import logging


class ProviderLogFilter(logging.Filter):
    def filter(self, record):
        record.msg = 'Provider SDK event; detalhes externos omitidos por segurança.'
        record.args = ()
        record.exc_info = None
        record.exc_text = None
        record.stack_info = None
        return True


def configure_safe_logging():
    from agno.utils.log import agent_logger, team_logger, workflow_logger
    for logger in (agent_logger, team_logger, workflow_logger):
        logger.addFilter(ProviderLogFilter())
    # SDK debug logging may contain request headers. Disable their namespaces.
    for name in ('openai', 'anthropic', 'httpx', 'httpcore', 'google.genai'):
        logging.getLogger(name).setLevel(logging.CRITICAL + 1)
