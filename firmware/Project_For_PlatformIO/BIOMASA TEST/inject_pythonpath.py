Import("env")
import os

# Ensure project root is on PYTHONPATH so stub modules (e.g., intelhex) are found when PlatformIO tools run.
project_dir = os.path.abspath(env.subst("$PROJECT_DIR"))
current = env["ENV"].get("PYTHONPATH", "")
env["ENV"]["PYTHONPATH"] = os.pathsep.join(filter(None, [project_dir, current]))
