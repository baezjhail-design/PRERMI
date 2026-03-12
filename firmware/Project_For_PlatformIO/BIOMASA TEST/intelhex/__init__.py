class HexRecordError(Exception):
    """Placeholder error to mimic intelhex when full package is unavailable."""


class IntelHex:
    """Minimal stub of IntelHex used only to avoid import errors in esptool.

    This stub does not implement HEX parsing. If a HEX file is ever passed,
    it will raise HexRecordError to make the limitation explicit.
    """

    def __init__(self):
        self._data = {}

    def loadhex(self, filename):
        raise HexRecordError("intelhex stub: HEX parsing not supported in this environment")

    def addresses(self):
        return list(self._data.keys())

    def tobinfile(self, f, start=None, end=None):
        raise HexRecordError("intelhex stub: HEX parsing not supported in this environment")
